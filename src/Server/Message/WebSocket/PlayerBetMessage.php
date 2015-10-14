<?php

namespace Blackjack\Server\Message\WebSocket;

use Blackjack\Game\Player;

class PlayerBetMessage implements WebSocketMessageInterface
{
    /** @var Player */
    private $player;

    /** @var int */
    private $betAmount;

    /**
     * PlayerBetMessage constructor.
     *
     * @param Player $player
     * @param int    $betAmount
     */
    public function __construct(Player $player, $betAmount)
    {
        $this->player    = $player;
        $this->betAmount = $betAmount;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'player_bet';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'seatIndex' => $this->player->getSeat()->getSeatIndex(),
            'betAmount' => $this->betAmount,
            'money'     => $this->player->getChips()->getAmount(),
        ];
    }
}
