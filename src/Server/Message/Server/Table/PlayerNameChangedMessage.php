<?php

namespace Blackjack\Server\Message\Server\Table;

use Blackjack\Game\Player;
use Blackjack\Server\Message\Server\ServerMessageInterface;

/**
 * Message sent to clients broadcasting that a player at the table has changed their name.
 */
class PlayerNameChangedMessage implements ServerMessageInterface
{
    /** @var Player */
    private $player;

    /**
     * @param Player $player
     */
    public function __construct($player)
    {
        $this->player = $player;
    }

    public function getAlias()
    {
        return 'name_change';
    }

    public function toArray()
    {
        return [
            'seatIndex' => $this->player->getSeat()->getSeatIndex(),
            'name'      => $this->player->getName(),
        ];
    }
}
