<?php

namespace Blackjack\Server\Message\Server\Table;

use Blackjack\Game\Card;
use Blackjack\Game\Hand;
use Blackjack\Game\HandBetPair;
use Blackjack\Game\Player;
use Blackjack\Game\Table;
use Blackjack\Game\TableSeat;
use Blackjack\Server\Message\Server\ServerMessageInterface;

class SendStateMessage implements ServerMessageInterface
{
    /** @var Table */
    private $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function getAlias()
    {
        return 'table_state';
    }

    private function serializeHand(Hand $hand = null)
    {
        if ($hand === null) {
            return null;
        }

        return [
            'value' => $hand->getValue(),
            'cards' => $hand->getCards()->map(function (Card $card) {
                return [
                    'rank' => $card->getRank(),
                    'suit' => $card->getSuit(),
                ];
            })->elements(),
        ];
    }

    private function serializePlayer(Player $player = null)
    {
        return [
            'name'  => $player->getName(),
            'money' => $player->getChips()->getAmount(),
        ];
    }

    public function toArray()
    {
        return [
            'dealerHand' => $this->serializeHand($this->table->getDealerHand()),
            'seats'      => $this->table->getSeats()->map(function (TableSeat $seat) {
                if ($seat->isFree()) {
                    return [
                        'seatIndex' => $seat->getSeatIndex(),
                        'free'      => true,
                    ];
                }

                return [
                    'seatIndex' => $seat->getSeatIndex(),
                    'player'    => $this->serializePlayer($seat->getPlayer()),
                    'hands'     => $seat->getHands()->map(function (HandBetPair $hand) {
                        return [
                            'hand' => $this->serializeHand($hand->getHand()),
                            'bet'  => $hand->getBet(),
                        ];
                    })->elements(),
                ];
            })->elements(),
        ];
    }
}
