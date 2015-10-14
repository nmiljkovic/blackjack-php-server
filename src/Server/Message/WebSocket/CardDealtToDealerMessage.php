<?php

namespace Blackjack\Server\Message\WebSocket;

use Blackjack\Game\Card;

class CardDealtToDealerMessage implements WebSocketMessageInterface
{
    /** @var Card */
    private $card;

    public function __construct(Card $card)
    {
        $this->card = $card;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'card_dealt_to_dealer';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'card' => [
                'rank' => $this->card->getRank(),
                'suit' => $this->card->getSuit(),
            ],
        ];
    }
}
