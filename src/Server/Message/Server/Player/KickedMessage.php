<?php

namespace Blackjack\Server\Message\Server\Player;

use Blackjack\Server\Message\Server\ServerMessageInterface;

class KickedMessage implements ServerMessageInterface
{
    const REASON_INACTIVE = 'inactive';
    const REASON_INSUFFICIENT_FUNDS = 'insufficient_funds';

    /** @var string */
    private $reason;

    public function __construct($reason)
    {
        if (!in_array($reason, [self::REASON_INACTIVE, self::REASON_INSUFFICIENT_FUNDS], true)) {
            throw new \LogicException('Invalid reason '.$reason);
        }

        $this->reason = $reason;
    }

    public function getAlias()
    {
        return 'kicked';
    }

    public function toArray()
    {
        return [
            'reason' => $this->reason,
        ];
    }
}
