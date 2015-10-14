<?php

namespace Blackjack\Game;

use React\Promise\Promise;

abstract class Player
{
    /** @var string */
    private $name;

    /** @var TableSeat */
    private $seat;

    /** @var Chips */
    private $chips;

    /**
     * Player constructor.
     *
     * @param string    $name
     * @param TableSeat $seat
     * @param int       $chips
     */
    public function __construct($name, $seat, $chips)
    {
        $this->name  = $name;
        $this->seat  = $seat;
        $this->chips = new Chips($chips);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->getSeat()->getTable();
    }

    /**
     * @return TableSeat
     */
    public function getSeat()
    {
        return $this->seat;
    }

    /**
     * @param TableSeat $seat
     */
    public function setSeat($seat)
    {
        $this->seat = $seat;
    }

    /**
     * @return Chips
     */
    public function getChips()
    {
        return $this->chips;
    }

    /**
     * @param int $minimum
     * @param int $maximum
     *
     * @return Promise
     */
    public abstract function requestBet($minimum, $maximum);

    /**
     * @param HandBetPair $hand
     * @param DealerHand  $dealerHand
     *
     * @return Promise
     */
    public abstract function requestAction(HandBetPair $hand, DealerHand $dealerHand);
}
