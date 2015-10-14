<?php

namespace Blackjack\Server\Message\WebSocket;

use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;

class PlayerHandResultMessage implements WebSocketMessageInterface
{
    /** @var Player */
    private $player;

    /** @var HandBetPair */
    private $hand;

    /** @var int */
    private $winnings;

    public function __construct(Player $player, HandBetPair $hand, $winnings)
    {
        $this->player   = $player;
        $this->hand     = $hand;
        $this->winnings = $winnings;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'hand_result';
    }

    private function getResult()
    {
        if ($this->winnings === $this->hand->getBet()) {
            return [
                'status'   => 'push',
                'winnings' => $this->winnings,
            ];
        } elseif ($this->winnings >= $this->hand->getBet()) {
            return [
                'status'   => 'win',
                'winnings' => $this->winnings,
            ];
        } else {
            return [
                'status' => 'lose',
            ];
        }
    }

    private function findHandIndex()
    {
        $index = 0;
        foreach ($this->player->getSeat()->getHands() as $hand) {
            if ($hand === $this->hand) {
                return $index;
            }
            $index++;
        }

        throw new \RuntimeException('Hand not found');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'seatIndex' => $this->player->getSeat()->getSeatIndex(),
            'handIndex' => $this->findHandIndex(),
            'result'    => $this->getResult(),
        ];
    }
}
