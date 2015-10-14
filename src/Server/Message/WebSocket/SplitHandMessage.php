<?php

namespace Blackjack\Server\Message\WebSocket;

use Blackjack\Game\Card;
use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;

class SplitHandMessage implements WebSocketMessageInterface
{
    /** @var Player */
    private $player;

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'split_hand';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'seatIndex' => $this->player->getSeat()->getSeatIndex(),
            'hands'     => $this->player->getSeat()->getHands()->map(function (HandBetPair $hand) {
                return [
                    'hand' => [
                        'value' => $hand->getHand()->getValue(),
                        'cards' => $hand->getHand()->getCards()->map(function (Card $card) {
                            return [
                                'rank' => $card->getRank(),
                                'suit' => $card->getSuit(),
                            ];
                        })->elements(),
                    ],
                    'bet'  => $hand->getBet(),
                ];
            })->elements(),
        ];
    }
}
