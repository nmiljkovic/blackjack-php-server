<?php

namespace Blackjack\Server\Message\Server\Player;

use Blackjack\Server\Message\Server\ServerMessageInterface;

/**
 * A message sent to the client when the "PlayerBetMessage" received from
 * the client is invalid (i.e. insufficient amount of funds)
 */
class BetRejectedMessage implements ServerMessageInterface
{
    const REASON_INSUFFICIENT_FUNDS = 'insufficient_funds';

    /** @var string */
    private $reason;

    public function __construct($reason)
    {
        if (!in_array($reason, [self::REASON_INSUFFICIENT_FUNDS], true)) {
            throw new \InvalidArgumentException('Invalid reason specified');
        }

        $this->reason = $reason;
    }

    public function getAlias()
    {
        return 'bet_rejected';
    }

    public function toArray()
    {
        return [
            'reason' => $this->reason,
        ];
    }
}
