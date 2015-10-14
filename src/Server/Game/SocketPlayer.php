<?php

namespace Blackjack\Server\Game;

use Blackjack\Game\DealerHand;
use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;
use Blackjack\Server\Connection;
use Blackjack\Server\Message\Client\PlayerActionMessage;
use Blackjack\Server\Message\Client\PlayerBetMessage;
use Blackjack\Server\Message\Server\Player\RequestActionMessage;
use Blackjack\Server\Message\Server\Player\RequestBetMessage;
use React\Promise\Promise;

class SocketPlayer extends Player
{
    /** @var Connection */
    private $connection;

    public function __construct($name, $seat, $chips, $socketConnection, $connectionManager)
    {
        parent::__construct($name, $seat, $chips);
        $this->connection = new Connection($this, $socketConnection, $connectionManager);
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param int $minimum
     * @param int $maximum
     *
     * @return Promise
     */
    public function requestBet($minimum, $maximum)
    {
        $this->connection->sendMessage(new RequestBetMessage($this, $minimum, $maximum));

        return $this->connection->waitOnMessage(PlayerBetMessage::class)
            ->then(function (PlayerBetMessage $message) {
                return $message->getAmount();
            });
    }

    /**
     * @param HandBetPair $hand
     * @param DealerHand  $dealerHand
     *
     * @return Promise
     */
    public function requestAction(HandBetPair $hand, DealerHand $dealerHand)
    {
        $this->connection->sendMessage(new RequestActionMessage($this, $dealerHand, $hand->getHand()));

        return $this->connection->waitOnMessage(PlayerActionMessage::class);
    }
}
