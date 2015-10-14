<?php

namespace Blackjack\Game;

class HandBetPair
{
    /** @var int */
    private $bet;

    /** @var Hand */
    private $hand;

    /** @var bool */
    private $stand = false;

    public function __construct(Hand $hand, $bet = 0)
    {
        $this->hand = $hand;
        $this->bet  = $bet;
    }

    /**
     * @return int
     */
    public function getBet()
    {
        return $this->bet;
    }

    /**
     * @param int $amount
     */
    public function increaseBet($amount)
    {
        $this->bet += $amount;
    }

    /**
     * @return Hand
     */
    public function getHand()
    {
        return $this->hand;
    }

    /**
     * @return boolean
     */
    public function isStand()
    {
        return $this->stand;
    }

    /**
     * @param boolean $stand
     */
    public function setStand($stand)
    {
        $this->stand = $stand;
    }
}
