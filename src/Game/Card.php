<?php

namespace Blackjack\Game;

class Card
{
    const SUIT_SPADES = 'S';
    const SUIT_CLUBS = 'C';
    const SUIT_HEARTS = 'H';
    const SUIT_DIAMONDS = 'D';

    const RANK_ACE = 1;
    const RANK_JACK = 12;
    const RANK_QUEEN = 13;
    const RANK_KING = 14;

    const BLACKJACK = 21;

    /** @var int */
    private $rank;

    /** @var string */
    private $suit;

    /**
     * Card constructor.
     *
     * @param int    $rank
     * @param string $suit
     */
    public function __construct($rank, $suit)
    {
        if (!in_array($suit, [self::SUIT_SPADES, self::SUIT_CLUBS, self::SUIT_HEARTS, self::SUIT_DIAMONDS], true)) {
            throw new \InvalidArgumentException('Invalid card suit');
        }

        if ($rank === 11) {
            $rank = self::RANK_ACE;
        }

        if ($rank < self::RANK_ACE || $rank > self::RANK_KING) {
            throw new \InvalidArgumentException('Invalid card rank');
        }

        $this->rank = $rank;
        $this->suit = $suit;
    }

    /**
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @return string
     */
    public function getSuit()
    {
        return $this->suit;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        if ($this->rank >= self::RANK_JACK) {
            return 10;
        } else {
            return $this->rank;
        }
    }

    public function getSuitAsString()
    {
        switch ($this->suit) {
            case self::SUIT_SPADES:
                return 'Spades';
            case self::SUIT_CLUBS:
                return 'Clubs';
            case self::SUIT_HEARTS:
                return 'Hearts';
            case self::SUIT_DIAMONDS:
                return 'Diamonds';
            default:
                throw new \BadMethodCallException();
        }
    }

    public function getValueAsString()
    {
        switch ($this->rank) {
            case self::RANK_ACE:
                return 'Ace';
            case self::RANK_JACK:
                return 'Jack';
            case self::RANK_QUEEN:
                return 'Queen';
            case self::RANK_KING:
                return 'King';
            default:
                return (string) $this->rank;
        }
    }

    public function __toString()
    {
        return sprintf('%s of %s', $this->getValueAsString(), $this->getSuitAsString());
    }

    public function copy()
    {
        return new self($this->rank, $this->suit);
    }
}
