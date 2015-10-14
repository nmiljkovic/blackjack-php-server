<?php

namespace Blackjack\Server;

use Blackjack\Game\Table;
use Blackjack\Game\TableSeat;
use Blackjack\Server\Game\WebSocketObservableTable;
use Blackjack\Server\Message\WebSocket\TableClosedMessage;
use Icecave\Collections\Vector;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface as WebSocketConnection;
use Ratchet\MessageComponentInterface;

class WebSocketManager implements MessageComponentInterface
{
    /** @var Vector|WebSocketConnection[] */
    private $connections = [];

    /** @var \SplObjectStorage */
    private $connectionTableMap;

    /** @var \SplObjectStorage */
    private $tableConnectionMap;

    /** @var TableManager */
    private $tableManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(TableManager $tableManager, LoggerInterface $logger)
    {
        $this->tableManager       = $tableManager;
        $this->connectionTableMap = new \SplObjectStorage();
        $this->tableConnectionMap = new \SplObjectStorage();
        $this->logger             = $logger;

        $this->connections = new Vector();
    }

    public function onOpen(WebSocketConnection $connection)
    {
        if (count($this->connections) > 100) {
            $connection->close();

            return;
        }

        $this->connections->pushBack($connection);
    }

    public function onClose(WebSocketConnection $connection)
    {
        if ($this->isSubscribed($connection)) {
            $this->unsubscribe($connection);
        }

        $this->connections = $this->connections->filter(function ($c) use ($connection) {
            return $c !== $connection;
        });
    }

    public function onError(WebSocketConnection $connection, \Exception $e)
    {
    }

    public function onMessage(WebSocketConnection $connection, $msg)
    {
        $message = \Blackjack\json_parse($msg);
        if (!isset($message['alias'])) {
            return;
        }

        $alias = $message['alias'];
        if ($alias === 'subscribe') {
            if ($this->isSubscribed($connection) || !isset($message['table_id'])) {
                $connection->close();

                return;
            }

            $tableId = $message['table_id'];

            /** @var WebSocketObservableTable $table */
            $table = $this->tableManager->getTableById($tableId);
            if ($table === null) {
                return;
            }

            $this->subscribe($connection, $table);

            return;
        }

        if ($alias === 'unsubscribe') {
            if (!$this->isSubscribed($connection)) {
                return;
            }

            $this->unsubscribe($connection);

            return;
        }

        if ($alias === 'query') {
            $connection->send(json_encode([
                'alias' => 'tables',
                'data'  => [
                    'tables' => $this->tableManager->getTables()->map(function (Table $table) {
                        return [
                            'id'      => $table->getId(),
                            'players' => $table->getSeats()
                                ->filter(function (TableSeat $seat) {
                                    return $seat->getPlayer() !== null;
                                })
                                ->map(function (TableSeat $seat) {
                                    return [
                                        'player' => $seat->getPlayer()->getName(),
                                    ];
                                })->elements(),
                        ];
                    })->elements(),
                ],
            ]));

            return;
        }
    }

    public function closeTable(WebSocketObservableTable $table)
    {
        $table->broadcast(new TableClosedMessage($table));

        $connections = $this->getConnectionsSubscribedToTable($table);
        foreach ($connections as $connection) {
            $this->unsubscribe($connection);
        }
    }

    private function isSubscribed(WebSocketConnection $connection)
    {
        return $this->connectionTableMap->offsetExists($connection);
    }

    private function subscribe(WebSocketConnection $connection, WebSocketObservableTable $table)
    {
        $this->connectionTableMap->offsetSet($connection, $table);
        $table->addConnection($connection);

        $connections = $this->getConnectionsSubscribedToTable($table);
        $connections->pushBack($connection);
        $this->tableConnectionMap->offsetSet($table, $connections);
    }

    private function unsubscribe(WebSocketConnection $connection)
    {
        /** @var WebSocketObservableTable $table */
        $table = $this->connectionTableMap->offsetGet($connection);
        $table->removeConnection($connection);
        $this->connectionTableMap->offsetUnset($connection);

        $connections = $this->getConnectionsSubscribedToTable($table)
            ->filter(function (WebSocketConnection $c) use ($connection) {
                return $c !== $connection;
            });

        if (count($connections)) {
            $this->tableConnectionMap->offsetSet($table, $connections);
        } else {
            $this->tableConnectionMap->offsetUnset($table);
        }
    }

    /**
     * @param WebSocketObservableTable $table
     *
     * @return Vector|WebSocketConnection[]
     */
    private function getConnectionsSubscribedToTable(WebSocketObservableTable $table)
    {
        try {
            return $this->tableConnectionMap->offsetGet($table);
        } catch (\Exception $e) {
            return new Vector();
        }
    }
}
