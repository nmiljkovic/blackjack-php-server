<?php

namespace Blackjack\Game;

use Icecave\Collections\Vector;

class Hand
{
    /** @var Vector|Card[] */
    private $cards;

    /** @var bool */
    private $initialHand;

    public function __construct($initialHand = true)
    {
        $this->initialHand = $initialHand;
        $this->cards       = new Vector();
    }

    /**
     * @param Card $card
     */
    public function addCard(Card $card)
    {
        $this->cards->pushBack($card);
    }

    /**
     * @return Vector|Card[]
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * @return int
     */
    public function getCardCount()
    {
        return count($this->cards);
    }

    /**
     * @return boolean
     */
    public function isInitialHand()
    {
        return $this->initialHand;
    }

    /**
     * The player can split a pair of cards into two different cards.
     * It can only be done at the start of the deal (ie. when the player has two cards)
     * and the cards have the same value.
     *
     * @return bool
     */
    public function canSplitPair()
    {
        return $this->initialHand && count($this->cards) === 2 && $this->cards[0]->getValue() === $this->cards[1]->getValue();
    }

    /**
     * Splits this pair into two decks containing single cards.
     *
     * @return Vector|Hand[]
     */
    public function splitPair()
    {
        if (!$this->canSplitPair()) {
            throw new \LogicException('Cannot split this hand.');
        }

        $hand1 = new Hand(false);
        $hand1->addCard($this->cards[0]);
        $hand2 = new Hand(false);
        $hand2->addCard($this->cards[1]);

        return new Vector([$hand1, $hand2]);
    }

    /**
     * Get hand numeric value
     *
     * @return int
     */
    public function getValue()
    {
        $recursiveCalculator = function ($value, $index) use (&$recursiveCalculator) {
            $cards     = $this->getCards();
            $cardCount = count($cards);
            while ($index < $cardCount) {
                $card = $cards[$index];

                if ($card->getRank() === Card::RANK_ACE) {
                    $highValue = $recursiveCalculator($value + 11, $index + 1);
                    $lowValue  = $recursiveCalculator($value + 1, $index + 1);

                    if ($highValue > $lowValue && $highValue <= 21) {
                        return $highValue;
                    } else {
                        return $lowValue;
                    }
                }

                $value += $card->getValue();

                $index++;
            }

            return $value;
        };

        return $recursiveCalculator(0, 0);
    }

    /**
     * @return bool
     */
    public function isBusted()
    {
        return $this->getValue() > 21;
    }

    /**
     * Returns true if this hand is a blackjack.
     * A blackjack only counts if the player has 2 initial cards and their value is 21.
     *
     * @return bool
     */
    public function isBlackjack()
    {
        return $this->getCardCount() === 2 && $this->is21();
    }

    /**
     * @return bool
     */
    public function is21()
    {
        return $this->getValue() === 21;
    }

    /**
     * @return bool
     */
    public function is17()
    {
        return $this->getValue() === 17;
    }

    /**
     * Returns true if this hand beats other hand.
     *
     * @param DealerHand $dealerHand
     *
     * @return bool
     */
    public function beatsHand(DealerHand $dealerHand)
    {
        if ($this->isBusted()) {
            return false;
        }

        return $dealerHand->isBusted() || ($this->getValue() > $dealerHand->getValue());
    }

    /**
     * @param DealerHand $dealerHand
     *
     * @return bool
     */
    public function drawnWithHand(DealerHand $dealerHand)
    {
        if ($this->isBusted() && $dealerHand->isBusted()) {
            return false;
        }

        return $dealerHand->getValue() === $this->getValue();
    }

    public function copy()
    {
        $hand = new self();
        foreach ($this->cards as $card) {
            $hand->addCard($card->copy());
        }

        return $hand;
    }

    public function __toString()
    {
        return implode(', ', $this->getCards()->elements());
    }
}
