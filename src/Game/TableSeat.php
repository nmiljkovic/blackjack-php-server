<?php

namespace Blackjack\Game;

use Blackjack\Server\Game\ServerPlayer;
use Icecave\Collections\Vector;

class TableSeat
{
    /** @var int */
    private $seatIndex;

    /** @var Table */
    private $table;

    /** @var Player */
    private $player;

    /** @var Vector|HandBetPair[] */
    private $hands;

    /**
     * Indicates if the player has placed a bet and can play this hand.
     *
     * @var boolean
     */
    private $inPlay = false;

    public function __construct($seatIndex, Table $table, Player $player = null)
    {
        $this->seatIndex = $seatIndex;
        $this->table     = $table;
        $this->player    = $player;

        $this->hands = new Vector();
    }

    /**
     * @return int
     */
    public function getSeatIndex()
    {
        return $this->seatIndex;
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @param Player $player
     */
    public function setPlayer($player)
    {
        $this->player = $player;
    }

    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return Vector|HandBetPair[]
     */
    public function getHands()
    {
        return $this->hands;
    }

    /**
     * Resets player hands.
     */
    public function resetHand()
    {
        $this->hands = new Vector([new HandBetPair(new Hand())]);
    }

    /**
     * Set hands
     *
     * @param Vector|HandBetPair[] $hands
     */
    public function setHands($hands)
    {
        if (!$hands instanceof Vector) {
            $hands = new Vector($hands);
        }
        $this->hands = $hands;
    }

    /**
     * @return boolean
     */
    public function isInPlay()
    {
        return $this->inPlay;
    }

    /**
     * @param boolean $inPlay
     */
    public function setInPlay($inPlay)
    {
        $this->inPlay = $inPlay;
    }

    /**
     * @return bool
     */
    public function isServerBot()
    {
        return $this->player instanceof ServerPlayer;
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return $this->player === null;
    }
}
