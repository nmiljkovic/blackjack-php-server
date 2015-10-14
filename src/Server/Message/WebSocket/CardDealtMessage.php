<?php

namespace Blackjack\Server\Message\WebSocket;

use Blackjack\Game\Card;
use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;

class CardDealtMessage implements WebSocketMessageInterface
{
    /** @var Player */
    private $player;

    /** @var HandBetPair */
    private $hand;

    /** @var Card */
    private $card;

    public function __construct(Player $player, HandBetPair $hand, Card $card)
    {
        $this->player = $player;
        $this->hand   = $hand;
        $this->card   = $card;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'card_dealt';
    }

    private function findHandIndex()
    {
        $index = 0;
        foreach ($this->player->getSeat()->getHands() as $hand) {
            if ($hand !== $this->hand) {
                $index++;
                continue;
            }

            return $index;
        }

        throw new \RuntimeException('Invalid hand');
    }

    private function serializeCard()
    {
        return [
            'rank' => $this->card->getRank(),
            'suit' => $this->card->getSuit(),
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'seatIndex' => $this->player->getSeat()->getSeatIndex(),
            'handIndex' => $this->findHandIndex(),
            'card'      => $this->serializeCard(),
        ];
    }
}
