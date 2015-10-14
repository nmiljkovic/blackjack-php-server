<?php

namespace Blackjack\Server\Message\Server\Table;

use Blackjack\Game\TableSeat;
use Blackjack\Server\Message\Server\ServerMessageInterface;

/**
 * Message sent to clients notifying them that a player has joined the table.
 */
class PlayerJoinedTableMessage implements ServerMessageInterface
{
    /** @var TableSeat */
    private $seat;

    public function __construct(TableSeat $seat)
    {
        $this->seat = $seat;
    }

    public function getAlias()
    {
        return 'player_joined_table';
    }

    public function toArray()
    {
        return [
            'seatIndex' => $this->seat->getSeatIndex(),
            'player'    => [
                'name'  => $this->seat->getPlayer()->getName(),
                'money' => $this->seat->getPlayer()->getChips()->getAmount(),
            ],
        ];
    }
}
