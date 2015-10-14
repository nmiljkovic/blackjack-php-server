<?php

namespace Blackjack\Game;

class DealerHand extends Hand
{
    public function beatsHand(DealerHand $dealerHand)
    {
        throw new \BadMethodCallException('Unable to compare two dealer hands');
    }

    public function canSplitPair()
    {
        return false;
    }

    /**
     * @return Card
     */
    public function getFaceUpCard()
    {
        return $this->getCards()[1];
    }
}
