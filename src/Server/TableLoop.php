<?php

namespace Blackjack\Server;

use Blackjack\Game\HandAction;
use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;
use Blackjack\Game\Table;
use Blackjack\Server\Message\Server\Player\KickedMessage;
use Blackjack\TableLogger;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;

class TableLoop
{
    const PAUSE_SPEED = 0.5;
    const LONG_PAUSE_SPEED = 1;
    const LONGEST_PAUSE_SPEED = 3;

    /** @var Table */
    private $table;

    /** @var ConnectionManager */
    private $connectionManager;

    /** @var bool */
    private $running;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ConnectionManager $connectionManager, LoggerInterface $logger, Table $table)
    {
        $this->table             = $table;
        $this->connectionManager = $connectionManager;
        $this->running           = true;
        $this->logger            = new TableLogger($logger, $table);

        \Blackjack\Promise\timedResolve(self::LONG_PAUSE_SPEED)
            ->then(function () {
                $this->startNewHand();
            });
    }

    public function startNewHand()
    {
        if (!$this->running) {
            return;
        }

        $this->resetTable()
            ->then(function () {
                $this->logger->info('Accepting bets');

                return $this->requestBets();
            })
            ->then(function () {
                $this->logger->info('Dealing initial hand');

                return $this->dealInitialCards();
            })
            ->then(function () {
                $this->logger->info('Playing hand');

                return $this->playHand();
            })
            ->then(function () {
                $this->logger->info('Playing dealer hand');

                return $this->playDealerHand();
            })
            ->then(function () {
                $this->logger->info('Paying out players');

                return $this->distributeWinnings();
            })
            ->always(function () {
                $this->logger->info('Pausing until next hand');

                return \Blackjack\Promise\timedResolve(self::LONGEST_PAUSE_SPEED)
                    ->then(function () {
                        $this->startNewHand();
                    });
            });
    }

    public function cancel()
    {
        $this->running = false;
    }

    /**
     * @return Promise
     */
    private function resetTable()
    {
        $this->table->resetTable();

        return \Blackjack\Promise\timedResolve(self::PAUSE_SPEED);
    }

    /**
     * Request player bets in parallel.
     * Players which do not respond in time are not allowed to play this hand.
     *
     * @return Promise
     */
    private function requestBets()
    {
        $promises = [];
        foreach ($this->table->getSeats() as $seat) {
            if ($seat->isFree()) {
                continue;
            }

            $player = $seat->getPlayer();
            if ($player->getChips()->getAmount() < $this->table->getMinimumBet()) {
                $this->connectionManager->kickPlayer($player, KickedMessage::REASON_INSUFFICIENT_FUNDS);
                continue;
            }

            $promises[] = $this->table->requestPlayerBet($player)
                ->then(function ($amount) use ($seat) {
                    $this->table->acceptPlayerBet($seat->getPlayer(), $seat->getHands()[0], $amount);
                    $seat->setInPlay(true);

                    $this->logger->info('Player bet', [
                        'player' => $seat->getPlayer()->getName(),
                        'amount' => $amount,
                        'stack'  => $seat->getPlayer()->getChips()->getAmount(),
                    ]);

                    return null;
                })
                ->otherwise(function (\Exception $reason) use ($seat) {
                    $seat->setInPlay(false);

                    $this->logger->info('Player unable to bet', [
                        'player' => $seat->getPlayer()->getName(),
                        'stack'  => $seat->getPlayer()->getChips()->getAmount(),
                        'reason' => $reason->getMessage(),
                    ]);

                    return null;
                });
        }

        return \React\Promise\all($promises)
            ->then(function () {
                return \Blackjack\Promise\timedResolve(self::PAUSE_SPEED);
            });
    }

    /**
     * Deal initial cards.
     *
     * @return Promise
     */
    private function dealInitialCards()
    {
        $promise = \React\Promise\resolve();

        for ($i = 0; $i < 2; $i++) {
            foreach ($this->table->getSeats() as $seat) {
                // Draw only into the initial hand
                // It is guaranteed that hands[0] exists if the seat is in play
                $promise = $promise->then(function () use ($seat) {
                    if ($seat->isInPlay()) {
                        return $this->table->dealCardToHand($seat->getPlayer(), $seat->getHands()[0]);
                    }
                });
            }

            $promise = $promise->then(function () {
                return $this->table->dealCardToDealer();
            });
        }

        return $promise;
    }

    /**
     * @return Promise
     */
    private function playHand()
    {
        $promise = \Blackjack\Promise\timedResolve(self::PAUSE_SPEED);

        foreach ($this->table->getSeats() as $seat) {
            $promise = $promise
                ->then(function () use ($seat) {
                    if (!$seat->isInPlay()) {
                        throw new \RuntimeException('Seat not in play!');
                    }

                    return $this->requestPlayerAction($seat->getPlayer(), $seat->getHands()[0]);
                });
        }

        return $promise;
    }

    /**
     * @param Player      $player
     * @param HandBetPair $hand
     *
     * @return Promise
     */
    private function requestPlayerAction(Player $player, HandBetPair $hand)
    {
        if ($hand->isStand() || $hand->getHand()->is21() || $hand->getHand()->isBusted()) {
            return \Blackjack\Promise\timedResolve(self::PAUSE_SPEED)
                ->then(function () use ($player) {
                    return $this->table->ensurePlayerActive($player);
                });
        }

        return $this->table->requestPlayerAction($player, $hand)
            ->then(function (HandAction $action) use ($player, $hand) {
                switch ($action->getAction()) {
                    case HandAction::ACTION_STAND:
                        $hand->setStand(true);

                        return $this->requestPlayerAction($player, $hand);
                    case HandAction::ACTION_HIT:
                        return $this->table->dealCardToHand($player, $hand)
                            ->then(function () use ($player, $hand) {
                                return $this->requestPlayerAction($player, $hand);
                            });
                    case HandAction::ACTION_DOUBLE_DOWN:
                        $currentBet = $hand->getBet();

                        return $this->table->acceptPlayerBet($player, $hand, $currentBet)
                            ->then(function () use ($player, $hand) {
                                $hand->setStand(true);

                                return $this->table->dealCardToHand($player, $hand);
                            })
                            ->then(function () use ($player, $hand) {
                                return $this->requestPlayerAction($player, $hand);
                            });
                    case HandAction::ACTION_SPLIT:
                        return $this->table->splitPlayerHand($player, $hand)
                            ->then(function () use ($player) {
                                return $this->table->dealCardToHand($player, $player->getSeat()->getHands()[0]);
                            })
                            ->then(function () use ($player) {
                                return $this->table->dealCardToHand($player, $player->getSeat()->getHands()[1]);
                            })
                            ->then(function () use ($player) {
                                return $this->requestPlayerAction($player, $player->getSeat()->getHands()[0]);
                            })
                            ->then(function () use ($player) {
                                return $this->requestPlayerAction($player, $player->getSeat()->getHands()[1]);
                            });
                    default:
                        // Invalid action received - treat as stand
                        $hand->setStand(true);

                        return $this->requestPlayerAction($player, $hand);
                }
            })
            ->otherwise(function (\Exception $reason) use ($player, $hand) {
                $hand->setStand(true);

                $this->logger->error('Error while playing hand', [
                    'player' => $player->getName(),
                    'reason' => $reason->getMessage(),
                ]);

                return $this->requestPlayerAction($player, $hand);
            });
    }

    /**
     * @return Promise
     */
    private function playDealerHand()
    {
        return \Blackjack\Promise\timedResolve(self::PAUSE_SPEED)
            ->then(function () {
                if ($this->table->getDealerHand()->getValue() >= 17) {
                    return null;
                }

                return $this->table->dealCardToDealer()
                    ->then(function () {
                        return $this->playDealerHand();
                    });
            });
    }

    /**
     * @return Promise
     */
    private function distributeWinnings()
    {
        $promise = \Blackjack\Promise\timedResolve(self::LONG_PAUSE_SPEED);

        foreach ($this->table->getSeats() as $seat) {
            if (!$seat->isInPlay()) {
                continue;
            }

            foreach ($seat->getHands() as $hand) {
                // Chain actions
                $promise = $promise->then(function () use ($seat, $hand) {
                    return $this->table->distributePlayerWinnings($seat->getPlayer(), $hand)
                        ->then(function ($winnings) use ($seat, $hand) {
                            if ($winnings === $hand->getBet()) {
                                $message = 'Player draws with dealer';
                            } elseif ($winnings > $hand->getBet()) {
                                $message = 'Player beats dealer';
                            } else {
                                $message = 'Player loses to dealer';
                            }

                            $this->logger->info($message, [
                                'player'     => $seat->getPlayer()->getName(),
                                'bet'        => $hand->getBet(),
                                'winnings'   => $winnings,
                                'playerHand' => (string) $hand->getHand(),
                                'dealerHand' => (string) $this->table->getDealerHand(),
                            ]);
                        });
                });
            }
        }

        return $promise;
    }
}
