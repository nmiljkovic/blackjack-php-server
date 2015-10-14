<?php

namespace Blackjack\Server\Message\Client;

class PlayerBetMessage
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
}
