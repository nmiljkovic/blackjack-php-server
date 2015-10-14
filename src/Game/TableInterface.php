<?php

namespace Blackjack\Game;

use Icecave\Collections\Vector;
use React\Promise\Promise;

interface TableInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getState();

    /**
     * @param string $state
     */
    public function setState($state);

    /**
     * @return Vector|TableSeat[]
     */
    public function getSeats();

    /**
     * @return DealerHand
     */
    public function getDealerHand();

    /**
     * @return int
     */
    public function getMinimumBet();

    /**
     * @return int
     */
    public function getMaximumBet();

    /**
     * @return Deck
     */
    public function getDeck();

    /**
     * @param Deck $deck
     */
    public function setDeck($deck);

    /**
     * @param int $index
     *
     * @return TableSeat
     */
    public function getSeatByIndex($index);

    /**
     * Deal card to player hand.
     *
     * @param Player      $player
     * @param HandBetPair $hand
     *
     * @return Promise
     */
    public function dealCardToHand(Player $player, HandBetPair $hand);

    /**
     * Deal card to dealer.
     *
     * @return Promise
     */
    public function dealCardToDealer();

    /**
     * Accepts player bet if player has enough funds.
     *
     * @param Player      $player
     * @param HandBetPair $hand
     * @param int         $amount
     *
     * @return Promise
     */
    public function acceptPlayerBet(Player $player, HandBetPair $hand, $amount);

    /**
     * Splits player hand if the hand contains a pair.
     *
     * @param Player      $player
     * @param HandBetPair $hand
     *
     * @return Promise
     */
    public function splitPlayerHand(Player $player, HandBetPair $hand);

    /**
     * Requests a bet from the player and resolves to an integer amount of the requested bet.
     *
     * @param Player $player
     *
     * @return Promise
     */
    public function requestPlayerBet(Player $player);

    /**
     * Requests a player action and resolves to the HandAction object.
     *
     * @param Player      $player
     * @param HandBetPair $hand
     *
     * @return Promise
     */
    public function requestPlayerAction(Player $player, HandBetPair $hand);

    /**
     * Makes sure the player is still playing at this table and resolves/rejects based on the condition.
     * This is currently a hack and a better solution is required.
     *
     * @param Player $player
     * @param        $value
     *
     * @return Promise
     */
    public function ensurePlayerActive(Player $player, $value = null);

    /**
     *
     *
     * @param Player      $player
     * @param HandBetPair $hand
     *
     * @return Promise
     */
    public function distributePlayerWinnings(Player $player, HandBetPair $hand);

    /**
     * Resets dealer hand and player hands.
     */
    public function resetTable();
}
