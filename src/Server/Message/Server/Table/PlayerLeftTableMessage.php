<?php

namespace Blackjack\Server\Message\Server\Table;

use Blackjack\Server\Message\Server\ServerMessageInterface;

class PlayerLeftTableMessage implements ServerMessageInterface
{
    /** @var int */
    private $seatIndex;

    /**
     * @param int $seatIndex
     */
    public function __construct($seatIndex)
    {
        $this->seatIndex = $seatIndex;
    }

    public function getAlias()
    {
        return 'player_left_table';
    }

    public function toArray()
    {
        return [
            'seatIndex' => $this->seatIndex,
        ];
    }
}
