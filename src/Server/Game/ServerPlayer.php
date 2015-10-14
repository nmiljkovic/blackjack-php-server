<?php

namespace Blackjack\Server\Game;

use Blackjack\Game\DealerHand;
use Blackjack\Game\HandAction;
use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;
use Blackjack\Server\ConnectionManager;
use React\Promise\Promise;

class ServerPlayer extends Player
{
    const RESPONSE_SPEED = 0.2;

    private $connectionManager;

    public function __construct($name, $seat, $chips, ConnectionManager $connectionManager)
    {
        parent::__construct($name, $seat, $chips);
        $this->connectionManager = $connectionManager;
    }

    /**
     * @param int $minimum
     * @param int $maximum
     *
     * @return Promise
     */
    public function requestBet($minimum, $maximum)
    {
        return \Blackjack\Promise\timedResolve(self::RESPONSE_SPEED)
            ->then(function () use ($minimum, $maximum) {
                if ($this->getSeat() === null) {
                    return \React\Promise\reject(new \RuntimeException('Player kicked'));
                }

                if ($this->getChips()->getAmount() < 200) {
                    $this->getChips()->setAmount(5000);
                }

                return \React\Promise\resolve(
                    min(
                        min(mt_rand($minimum, $minimum + 200), $this->getChips()->getAmount()),
                        $maximum
                    )
                );
            });
    }

    /**
     * @param HandBetPair $hand
     * @param DealerHand  $dealerHand
     *
     * @return Promise
     */
    public function requestAction(HandBetPair $hand, DealerHand $dealerHand)
    {
        return \Blackjack\Promise\timedResolve(self::RESPONSE_SPEED)
            ->then(function () use ($hand, $dealerHand) {
                if ($this->getSeat() === null) {
                    return \React\Promise\reject(new \RuntimeException('Player kicked'));
                }

                if ($hand->getHand()->canSplitPair()) {
                    return \React\Promise\resolve(new HandAction(HandAction::ACTION_SPLIT));
                }

                if ($hand->getHand()->getValue() === 10) {
                    return \React\Promise\resolve(new HandAction(HandAction::ACTION_DOUBLE_DOWN));
                }

                if ($hand->getHand()->getValue() < 17) {
                    return \React\Promise\resolve(new HandAction(HandAction::ACTION_HIT));
                } else {
                    return \React\Promise\resolve(new HandAction(HandAction::ACTION_STAND));
                }
            });
    }
}
