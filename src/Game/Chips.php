<?php

namespace Blackjack\Game;

use Blackjack\Exception\InsufficientFundsException;
use Blackjack\Exception\InvalidAmountException;

class Chips
{
    /** @var int */
    private $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     *
     * @throws InvalidAmountException
     */
    public function setAmount($amount)
    {
        if ($amount < 0) {
            throw new InvalidAmountException('Amount of chips cannot be negative');
        }

        $this->amount = $amount;
    }

    /**
     * @param int $amount
     */
    public function acceptWinnings($amount)
    {
        $this->amount += $amount;
    }

    /**
     * Reduces player chip size by the specified amount.
     *
     * @param int $amount
     *
     * @throws InsufficientFundsException
     * @throws InvalidAmountException
     */
    public function bet($amount)
    {
        if ($amount <= 0) {
            throw new InvalidAmountException('Bet must be positive');
        }

        if ($this->amount < $amount) {
            throw new InsufficientFundsException(sprintf('Requested bet $%d, but $%d available', $amount, $this->amount));
        }

        $this->amount -= $amount;
    }

    /**
     * Returns true if player can bet
     *
     * @param int $amount
     *
     * @return bool
     */
    public function canBet($amount)
    {
        return $this->amount >= $amount;
    }

    /**
     * @return bool
     */
    public function isDryStack()
    {
        return $this->amount === 0;
    }
}
