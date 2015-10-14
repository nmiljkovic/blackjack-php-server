<?php

namespace Blackjack\Game;

use Blackjack\Exception\IllegalBetException;
use Icecave\Collections\Vector;

class Table implements TableInterface
{
    const STATE_BETS = 'bets';
    const STATE_IN_PLAY = 'in_play';
    const STATE_DEALER_PLAY = 'dealer_play';
    const STATE_HAND_FINISHED = 'hand_finished';

    const ACTION_SPEED = 0.2;

    /** @var string */
    private $id;

    /** @var string */
    private $state = self::STATE_HAND_FINISHED;

    /** @var Vector|TableSeat[] */
    private $seats;

    /** @var DealerHand */
    private $dealerHand;

    /** @var Deck */
    private $deck;

    /** @var int */
    private $minimumBet = 50;

    /** @var int */
    private $maximumBet = 150;

    public function __construct($seats = 5)
    {
        if ($seats <= 0) {
            throw new \LogicException('Table must have at least 1 seat');
        }

        $this->seats = new Vector();
        for ($seat = 0; $seat < $seats; $seat++) {
            $this->seats->pushBack(new TableSeat($seat, $this));
        }

        $this->id = md5(uniqid('', true));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getSeats()
    {
        return $this->seats;
    }

    public function setState($state)
    {
        if (!in_array($state, [self::STATE_IN_PLAY, self::STATE_BETS, self::STATE_DEALER_PLAY, self::STATE_HAND_FINISHED], true)) {
            throw new \LogicException(sprintf('Invalid table state %s provided', $state));
        }

        $this->state = $state;
    }

    public function getDealerHand()
    {
        return $this->dealerHand;
    }

    public function resetTable()
    {
        if ($this->deck === null) {
            $this->deck = new Deck(10);
            $this->deck->shuffleCards();
        }

        if ($this->deck->getRemainingCards() < 30) {
            $this->deck->restockDeck();
            $this->deck->shuffleCards();
        }

        $this->dealerHand = new DealerHand();
        foreach ($this->getSeats() as $seat) {
            $seat->setHands([new HandBetPair(new Hand())]);
        }
    }

    public function getDeck()
    {
        return $this->deck;
    }

    public function setDeck($deck)
    {
        $this->deck = $deck;
    }

    public function onlySeatedByBots()
    {
        foreach ($this->getSeats() as $seat) {
            if (!$seat->isServerBot()) {
                return false;
            }
        }

        return true;
    }

    public function getSeatByIndex($index)
    {
        foreach ($this->getSeats() as $seat) {
            if ($seat->getSeatIndex() === $index) {
                return $seat;
            }
        }

        throw new \OutOfRangeException(printf('Table index %d not found', $index));
    }

    public function getMinimumBet()
    {
        return $this->minimumBet;
    }

    public function getMaximumBet()
    {
        return $this->maximumBet;
    }

    public function dealCardToHand(Player $player, HandBetPair $hand)
    {
        return \Blackjack\Promise\timedResolve(self::ACTION_SPEED)
            ->then(function () use ($player) {
                return $this->ensurePlayerActive($player);
            })
            ->then(function () use ($hand) {
                $card = $this->getDeck()->draw();
                $hand->getHand()->addCard($card);

                return $card;
            });
    }

    public function dealCardToDealer()
    {
        return \Blackjack\Promise\timedResolve(self::ACTION_SPEED)
            ->then(function () {
                $card = $this->getDeck()->draw();
                $this->getDealerHand()->addCard($card);

                return $card;
            });
    }

    public function acceptPlayerBet(Player $player, HandBetPair $hand, $amount)
    {
        try {
            $player->getChips()->bet($amount);
            $hand->increaseBet($amount);
        } catch (\Exception $reason) {
            return \React\Promise\reject($reason);
        }

        return \React\Promise\resolve();
    }

    public function splitPlayerHand(Player $player, HandBetPair $hand)
    {
        return \Blackjack\Promise\timedResolve(self::ACTION_SPEED * 2)
            ->then(function () use ($player) {
                return $this->ensurePlayerActive($player);
            })
            ->then(function () use ($player, $hand) {
                $betAmount = $hand->getBet();
                $player->getChips()->bet($betAmount);

                $splitHands = $hand->getHand()->splitPair();
                $hands      = [
                    new HandBetPair($splitHands[0], $betAmount),
                    new HandBetPair($splitHands[1], $betAmount),
                ];
                $player->getSeat()->setHands($hands);

                return $hands;
            });
    }

    public function requestPlayerBet(Player $player)
    {
        return $player->requestBet($this->minimumBet, $this->maximumBet)
            ->then(function ($bet) {
                return \Blackjack\Promise\timedResolve(self::ACTION_SPEED, $bet);
            })
            ->then(function ($bet) use ($player) {
                return $this->ensurePlayerActive($player, $bet);
            })
            ->then(function ($bet) {
                if ($bet < $this->getMinimumBet() || $bet > $this->getMaximumBet()) {
                    throw new IllegalBetException();
                }

                return $bet;
            });
    }

    public function requestPlayerAction(Player $player, HandBetPair $hand)
    {
        return $player->requestAction($hand, $this->getDealerHand())
            ->then(function ($action) {
                return \Blackjack\Promise\timedResolve(self::ACTION_SPEED, $action);
            })
            ->then(function ($action) use ($player) {
                return $this->ensurePlayerActive($player, $action);
            });
    }

    public function distributePlayerWinnings(Player $player, HandBetPair $hand)
    {
        return \Blackjack\Promise\timedResolve(self::ACTION_SPEED)
            ->then(function () use ($player) {
                return $this->ensurePlayerActive($player);
            })
            ->then(function () use ($player, $hand) {
                if ($hand->getHand()->beatsHand($this->dealerHand)) {
                    // Player wins
                    $winnings = $hand->getBet() +
                        (int) ($hand->getBet() * $this->computeWinningModifier($hand->getHand()));
                    $player->getChips()->acceptWinnings($winnings);
                } elseif ($hand->getHand()->drawnWithHand($this->dealerHand)) {
                    // It's a push
                    $winnings = $hand->getBet();
                    $player->getChips()->acceptWinnings($winnings);
                } else {
                    // Player lost
                    $winnings = 0;
                }

                return $winnings;
            });
    }

    public function ensurePlayerActive(Player $player, $value = null)
    {
        if ($player->getSeat() === null || $player->getTable() !== $this) {
            return \React\Promise\reject(new \RuntimeException('Player left table'));
        }

        return \React\Promise\resolve($value);
    }

    private function computeWinningModifier(Hand $playerHand)
    {
        if ($playerHand->isInitialHand() && $playerHand->isBlackjack()) {
            // Blackjack pays 3:2
            return 1.5;
        }

        return 1;
    }
}
