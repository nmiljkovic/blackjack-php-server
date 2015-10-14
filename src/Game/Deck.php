<?php

namespace Blackjack\Game;

use Icecave\Collections\Vector;

class Deck
{
    /** @var int */
    private $decks;

    /** @var Vector|Card[] */
    private $cards;

    /**
     * @param int $decks Number of decks to generate. The resulting number of cards will be $decks * 52.
     */
    public function __construct($decks = 1)
    {
        $this->decks = $decks;
        $this->restockDeck();
    }

    public function shuffleCards()
    {
        // https://en.wikipedia.org/wiki/Fisher%E2%80%93Yates_shuffle
        $cardCount = count($this->cards);
        for ($i = 0; $i < $cardCount - 1; $i++) {
            $j = mt_rand($i, $cardCount - 1);

            $tmp             = $this->cards[$j];
            $this->cards[$j] = $this->cards[$i];
            $this->cards[$i] = $tmp;
        }
    }

    /**
     * @return bool
     */
    public function hasCards()
    {
        return count($this->cards) > 0;
    }

    /**
     * @return Card
     */
    public function draw()
    {
        return $this->cards->popBack();
    }

    public function getRemainingCards()
    {
        return count($this->cards);
    }

    public function restockDeck()
    {
        $this->cards = new Vector();

        $suit = [Card::SUIT_CLUBS, Card::SUIT_SPADES, Card::SUIT_HEARTS, Card::SUIT_DIAMONDS];
        foreach ($suit as $cardSuit) {
            for ($rank = Card::RANK_ACE; $rank <= Card::RANK_KING; $rank++) {
                if ($rank === 11) {
                    continue;
                }

                $this->cards->pushBack(new Card($rank, $cardSuit));
            }
        }

        $cards = clone $this->cards;
        for ($deck = 0; $deck < $this->decks; $deck++) {
            $this->cards->append($cards);
        }
    }
}
