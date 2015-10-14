<?php

namespace Blackjack\Server;

use Blackjack\Game\Player;
use Blackjack\Game\Table;
use Blackjack\Game\TableSeat;
use Blackjack\Server\Game\PlayerFactory;
use Blackjack\Server\Game\ServerPlayer;
use Blackjack\Server\Game\SocketPlayer;
use Blackjack\Server\Game\WebSocketObservableTable;
use Blackjack\Server\Message\Client\PlayerSetNameMessage;
use Blackjack\Server\Message\Deserializer;
use Blackjack\Server\Message\Serializer;
use Blackjack\Server\Message\Server\Player\KickedMessage;
use Blackjack\Server\Message\Server\ServerMessageInterface;
use Blackjack\Server\Message\Server\Table\PlayerJoinedTableMessage as SocketPlayerJoinedTableMessage;
use Blackjack\Server\Message\Server\Table\PlayerLeftTableMessage as SocketPlayerLeftTableMessage;
use Blackjack\Server\Message\WebSocket\PlayerJoinedTableMessage as WebSocketPlayerJoinedTableMessage;
use Blackjack\Server\Message\WebSocket\PlayerLeftTableMessage as WebSocketPlayerLeftTableMessage;
use Blackjack\Server\Message\WebSocket\PlayerNameChangedMessage;
use Blackjack\Server\Message\WebSocket\SendStateMessage as WebSocketSendStateMessage;
use Icecave\Collections\Vector;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Socket\Connection as Socket;

class ConnectionManager
{
    /** @var int */
    private $connectionIndex = 0;

    /** @var int */
    private $maxConnections;

    /** @var LoopInterface */
    private $loop;

    /** @var Serializer */
    private $serializer;

    /** @var Deserializer */
    private $deserializer;

    /** @var TableManager */
    private $tableManager;

    /** @var WebSocketManager */
    private $webSocketManager;

    /** @var PlayerFactory */
    private $playerFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var Vector|Connection[] */
    private $connections;

    /**
     * ConnectionManager constructor.
     *
     * @param LoopInterface    $loop
     * @param Serializer       $serializer
     * @param Deserializer     $deserializer
     * @param TableManager     $tableManager
     * @param WebSocketManager $webSocketManager
     * @param PlayerFactory    $playerFactory
     * @param LoggerInterface  $logger
     * @param int              $maxConnections
     */
    public function __construct(LoopInterface $loop, Serializer $serializer, Deserializer $deserializer, TableManager $tableManager, WebSocketManager $webSocketManager, PlayerFactory $playerFactory, LoggerInterface $logger, $maxConnections = 500)
    {
        $this->loop             = $loop;
        $this->serializer       = $serializer;
        $this->deserializer     = $deserializer;
        $this->tableManager     = $tableManager;
        $this->webSocketManager = $webSocketManager;
        $this->playerFactory    = $playerFactory;
        $this->logger           = $logger;
        $this->maxConnections   = $maxConnections;

        $this->connections = new Vector();
    }

    public function accept(Socket $socket)
    {
        if (count($this->connections) >= $this->maxConnections) {
            $socket->write(['alias' => 'capacity_reached']);
            $socket->close();

            throw new \RuntimeException('Server capacity reached');
        }

        $player     = $this->playerFactory->newSocketPlayer(sprintf('PlayerBot%d', ++$this->connectionIndex), null, 5000, $socket);
        $connection = $player->getConnection();

        $this->connections->pushBack($connection);

        $this->handleSocketEvents($connection);
        $this->handlePlayerNameChange($connection);
        $this->handlePlayerConnection($connection);
    }

    /**
     * @param Player                 $player
     * @param ServerMessageInterface $message
     * @param int                    $timeout
     *
     * @return Promise
     */
    public function enqueueMessage(Player $player, ServerMessageInterface $message, $timeout = 0)
    {
        $deferred = new Deferred();

        $sendMessage = function () use ($player, $message, $deferred) {
            $connection = $this->getPlayerConnection($player);
            if (!$connection) {
                $deferred->reject(new \RuntimeException('Connection for player '.$player->getName().' does not exist'));

                return;
            }

            try {
                $serializedMessage = $this->serializer->serialize($message);
                // Newline is the message separator
                $connection->getSocket()->write($serializedMessage."\n");
                $connection->setLastMessageSentAt(new \DateTime());
                $deferred->resolve();
            } catch (\Exception $e) {
                try {
                    $connection->getSocket()->close();
                } catch (\Exception $e) {
                }

                $deferred->reject($e);
            }
        };

        if ($timeout > 0) {
            /** @noinspection PhpParamsInspection */
            $this->loop->addTimer($timeout, $sendMessage);
        } else {
            $sendMessage();
        }

        return $deferred->promise();
    }

    public function broadcastMessage(Table $table, ServerMessageInterface $message)
    {
        $promises = [];

        foreach ($table->getSeats() as $seat) {
            if (!$seat->isServerBot()) {
                $promises[] = $this->enqueueMessage($seat->getPlayer(), $message);
            }
        }

        return \React\Promise\all($promises);
    }

    public function broadcastMessageIgnoringPlayer(Table $table, Player $ignoredPlayer, ServerMessageInterface $message)
    {
        $promises = [];

        foreach ($table->getSeats() as $seat) {
            $player = $seat->getPlayer();
            if ($player === null || $player === $ignoredPlayer) {
                continue;
            }

            if (!$seat->isServerBot()) {
                $promises[] = $this->enqueueMessage($player, $message, 0);
            }
        }

        return \React\Promise\all($promises);
    }

    public function kickPlayer(Player $player, $reason)
    {
        if ($player instanceof ServerPlayer) {
            $this->removePlayerFromSeat($player->getSeat());
        } elseif ($player instanceof SocketPlayer) {
            $this->enqueueMessage($player, new KickedMessage($reason))
                ->always(function () use ($player) {
                    $player->getConnection()->getSocket()->close();
                });
        }
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    private function getPlayerConnection(Player $player)
    {
        foreach ($this->connections as $connection) {
            if ($connection->getPlayer() === $player) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * Watch for incoming data.
     *
     * @param Connection $connection
     */
    private function handleSocketEvents(Connection $connection)
    {
        $buffer = '';

        $connection->getSocket()->on('data', function ($data) use (&$buffer, $connection) {
            $buffer .= $data;

            while (($newlinePosition = strpos($buffer, "\n")) !== false) {
                $jsonMessage = substr($buffer, 0, $newlinePosition);
                $buffer      = substr($buffer, $newlinePosition + 1);

                try {
                    $connection->setLastMessageReceivedAt(new \DateTime());
                    $message = $this->deserializer->deserialize(\Blackjack\json_parse($jsonMessage));
                    $connection->notify($message);
                } catch (\Exception $e) {
                    // Invalid message
                    $connection->getSocket()->write(json_encode(['alias' => 'invalid_message']));
                    $connection->getSocket()->close();

                    return;
                }
            }

            // Limit buffer to 1 MB
            $bufferSizeLimit = 1024 * 1024;
            if (strlen($buffer) > $bufferSizeLimit) {
                $buffer = substr($buffer, strlen($buffer) - $bufferSizeLimit);
            }
        });

        $connection->getSocket()->on('close', function () use ($connection) {
            $this->handlePlayerDisconnection($connection);
        });
    }

    private function handlePlayerConnection(Connection $connection)
    {
        $player = $connection->getPlayer();
        $seat   = $this->tableManager->findOrCreateVacantSeat();

        if (!$seat->isFree()) {
            $this->removePlayerFromSeat($seat, $assignBot = false);
        }

        $this->assignPlayerToSeat($seat, $player);
    }

    private function handlePlayerDisconnection(Connection $connection)
    {
        $player = $connection->getPlayer();
        $seat   = $player->getSeat();

        $playerConnection = $this->getPlayerConnection($player);

        $this->connections = $this->connections->filter(function ($c) use ($playerConnection) {
            return $c !== $playerConnection;
        });

        $this->removePlayerFromSeat($seat, $assignBot = true);

        if (!$seat->getTable()->onlySeatedByBots() || count($this->tableManager->getTables()) === 1) {
            // Skip destroying table if this table is the last one standing
            // Or there's at least one computer bot connected via TCP
            return;
        }

        $this->destroyTable($seat->getTable());
    }

    private function destroyTable(Table $table)
    {
        if ($table instanceof WebSocketObservableTable) {
            $this->webSocketManager->closeTable($table);
        }

        $this->tableManager->closeTable($table);
    }

    private function assignPlayerToSeat(TableSeat $seat, Player $player)
    {
        if (!$seat->isFree()) {
            throw new \LogicException('Player can only be assigned to an empty or bot seat.');
        }

        $this->logger->info('Assigning player to seat', [
            'player'   => $player->getName(),
            'table_id' => $seat->getTable()->getId(),
        ]);

        $seat->setPlayer($player);
        $seat->setInPlay(false);
        $seat->resetHand();
        $player->setSeat($seat);
        $table = $seat->getTable();

        $this->broadcastMessageIgnoringPlayer($table, $player, new SocketPlayerJoinedTableMessage($seat));
        if ($table instanceof WebSocketObservableTable) {
            $table->broadcast(new WebSocketPlayerJoinedTableMessage($seat));
            $table->broadcast(new WebSocketSendStateMessage($table));
        }
    }

    private function removePlayerFromSeat(TableSeat $seat, $assignBot = true)
    {
        if ($seat->isFree()) {
            throw new \LogicException('Unable to remove player from empty seat.');
        }

        $this->logger->info('Removing player from seat', [
            'player'   => $seat->getPlayer()->getName(),
            'table_id' => $seat->getTable()->getId(),
        ]);

        $player = $seat->getPlayer();
        $table  = $seat->getTable();
        $seat->setPlayer(null);
        $seat->setInPlay(false);
        $seat->resetHand();
        $player->setSeat(null);

        $this->broadcastMessageIgnoringPlayer($table, $player, new SocketPlayerLeftTableMessage($seat->getSeatIndex()));
        if ($table instanceof WebSocketObservableTable) {
            $table->broadcast(new WebSocketPlayerLeftTableMessage($seat->getSeatIndex()));
        }

        if ($assignBot) {
            $bot = $this->playerFactory->newServerPlayer('Bot'.$seat->getSeatIndex(), $seat, 5000);
            $this->assignPlayerToSeat($seat, $bot);
        }
    }

    /**
     * Listens for PlayerSetNameMessage and changes the player name.
     *
     * @param Connection $connection
     */
    private function handlePlayerNameChange(Connection $connection)
    {
        $connection->waitOnMessage(PlayerSetNameMessage::class, 9999)
            ->then(function (PlayerSetNameMessage $message) use ($connection) {
                $player = $connection->getPlayer();
                if ($player->getSeat() === null || $player->getTable() === null) {
                    return;
                }

                if (!$message->isValid()) {
                    $this->logger->info('Player sent invalid name', [
                        'player'  => $player->getName(),
                        'newName' => $message->getNewName(),
                    ]);

                    return;
                }

                $player->setName($message->getNewName());
                $table = $player->getTable();
                if ($table instanceof WebSocketObservableTable) {
                    $table->broadcast(new PlayerNameChangedMessage($player));
                }
            });
    }
}
