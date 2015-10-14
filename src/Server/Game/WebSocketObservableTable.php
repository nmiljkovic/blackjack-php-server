<?php

namespace Blackjack\Server\Game;

use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;
use Blackjack\Game\Table;
use Blackjack\Server\Message\WebSocket\CardDealtMessage;
use Blackjack\Server\Message\WebSocket\CardDealtToDealerMessage;
use Blackjack\Server\Message\WebSocket\PlayerBetMessage;
use Blackjack\Server\Message\WebSocket\PlayerHandResultMessage;
use Blackjack\Server\Message\WebSocket\SendStateMessage;
use Blackjack\Server\Message\WebSocket\SplitHandMessage;
use Blackjack\Server\Message\WebSocket\WebSocketMessageInterface;
use Icecave\Collections\Vector;
use Ratchet\ConnectionInterface as WebSocketConnection;

class WebSocketObservableTable extends Table
{
    /** @var Vector|WebSocketConnection[] */
    private $connections;

    public function __construct($seats = 5)
    {
        parent::__construct($seats);
        $this->connections = new Vector();
    }

    public function addConnection(WebSocketConnection $connection)
    {
        $this->connections->pushBack($connection);
        $connection->send($this->serializeMessage(new SendStateMessage($this)));
    }

    public function removeConnection(WebSocketConnection $connection)
    {
        $this->connections = $this->connections->filter(function ($c) use ($connection) {
            return $c !== $connection;
        });
    }

    public function broadcast(WebSocketMessageInterface $message)
    {
        $stringMessage = $this->serializeMessage($message);
        foreach ($this->connections as $connection) {
            $connection->send($stringMessage);
        }
    }

    private function serializeMessage(WebSocketMessageInterface $message)
    {
        $alias = $message->getAlias();
        $data  = $message->toArray();

        return json_encode([
            'alias' => $alias,
            'data'  => $data,
        ]);
    }

    public function resetTable()
    {
        parent::resetTable();
        $this->broadcast(new SendStateMessage($this));
    }

    public function dealCardToHand(Player $player, HandBetPair $hand)
    {
        return parent::dealCardToHand($player, $hand)
            ->then(function ($card) use ($player, $hand) {
                $this->broadcast(new CardDealtMessage($player, $hand, $card));

                return $card;
            });
    }

    public function dealCardToDealer()
    {
        return parent::dealCardToDealer()
            ->then(function ($card) {
                $this->broadcast(new CardDealtToDealerMessage($card));

                return $card;
            });
    }

    public function acceptPlayerBet(Player $player, HandBetPair $hand, $amount)
    {
        return parent::acceptPlayerBet($player, $hand, $amount)
            ->then(function () use ($player, $amount) {
                $this->broadcast(new PlayerBetMessage($player, $amount));
            });
    }

    public function splitPlayerHand(Player $player, HandBetPair $hand)
    {
        return parent::splitPlayerHand($player, $hand)
            ->then(function ($hands) use ($player) {
                $this->broadcast(new SplitHandMessage($player));

                return $hands;
            });
    }

    public function distributePlayerWinnings(Player $player, HandBetPair $hand)
    {
        return parent::distributePlayerWinnings($player, $hand)
            ->then(function ($winnings) use ($player, $hand) {
                $this->broadcast(new PlayerHandResultMessage($player, $hand, $winnings));

                return $winnings;
            });
    }

}
