<?php

namespace Blackjack\Server;

use Blackjack\Game\Player;
use Blackjack\Server\Message\Server\ServerMessageInterface;
use Icecave\Collections\Vector;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Socket\Connection as SocketConnection;

class Connection
{
    /** @var Player */
    private $player;

    /** @var \React\Socket\Connection */
    private $socket;

    /** @var ConnectionManager */
    private $connectionManager;

    /** @var \DateTime */
    private $lastMessageSentAt;

    /** @var \DateTime */
    private $lastMessageReceivedAt;

    /** @var Vector */
    private $messageQueue;

    /**
     * @param Player            $player
     * @param SocketConnection  $socket
     * @param ConnectionManager $connectionManager
     */
    public function __construct(Player $player, SocketConnection $socket, ConnectionManager $connectionManager)
    {
        $this->player            = $player;
        $this->socket            = $socket;
        $this->connectionManager = $connectionManager;
        $this->messageQueue      = new Vector();

        $this->lastMessageSentAt     = new \DateTime();
        $this->lastMessageReceivedAt = new \DateTime();
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return SocketConnection
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @return \DateTime
     */
    public function getLastMessageSentAt()
    {
        return $this->lastMessageSentAt;
    }

    /**
     * @return \DateTime
     */
    public function getLastMessageReceivedAt()
    {
        return $this->lastMessageReceivedAt;
    }

    /**
     * @param \DateTime $lastMessageSentAt
     */
    public function setLastMessageSentAt($lastMessageSentAt)
    {
        $this->lastMessageSentAt = $lastMessageSentAt;
    }

    /**
     * @param \DateTime $lastMessageReceivedAt
     */
    public function setLastMessageReceivedAt($lastMessageReceivedAt)
    {
        $this->lastMessageReceivedAt = $lastMessageReceivedAt;
    }

    /**
     * @param ServerMessageInterface $message
     */
    public function sendMessage(ServerMessageInterface $message)
    {
        $this->connectionManager->enqueueMessage($this->getPlayer(), $message);
    }

    /**
     * @param $message
     */
    public function notify($message)
    {
        $class = get_class($message);

        foreach ($this->messageQueue as $queue) {
            if ($queue['class'] !== $class) {
                continue;
            }

            /** @var Deferred $deferred */
            $deferred = $queue['deferred'];
            $deferred->resolve($message);
        }

        $this->messageQueue = $this->messageQueue->filter(function ($entry) use ($class) {
            return $entry['class'] !== $class;
        });
    }

    /**
     * @param string $class
     * @param int    $timeout
     *
     * @return Promise
     */
    public function waitOnMessage($class, $timeout = 3)
    {
        $deferred = new Deferred();

        $this->messageQueue[] = [
            'class'    => $class,
            'deferred' => $deferred,
        ];

        return \Blackjack\Promise\timeout($deferred->promise(), $timeout)
            ->otherwise(function (\Exception $e) use ($deferred) {
                $this->messageQueue = $this->messageQueue->filter(function ($entry) use ($deferred) {
                    return $entry['deferred'] !== $deferred;
                });

                return \React\Promise\reject($e);
            });
    }
}
