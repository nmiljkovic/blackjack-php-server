<?php

namespace Blackjack\Server\Message\Server\Player;

use Blackjack\Game\Player;
use Blackjack\Server\Message\Server\ServerMessageInterface;

/**
 * Message sent to clients requesting them to place their bets.
 * Any clients without a bet placed will not participate in the next dealt hand.
 */
class RequestBetMessage implements ServerMessageInterface
{
    /** @var Player */
    private $player;

    /** @var int */
    private $minimumBet;

    /** @var int */
    private $maximumBet;

    public function __construct(Player $player, $minimumBet, $maximumBet)
    {
        $this->player     = $player;
        $this->minimumBet = $minimumBet;
        $this->maximumBet = $maximumBet;
    }

    public function toArray()
    {
        return [
            'minimumBet' => $this->minimumBet,
            'maximumBet' => $this->maximumBet,
            'money'      => $this->player->getChips()->getAmount(),
        ];
    }

    public function getAlias()
    {
        return 'request_bet';
    }
}
