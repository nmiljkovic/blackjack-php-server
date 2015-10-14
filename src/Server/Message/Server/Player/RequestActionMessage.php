<?php

namespace Blackjack\Server\Message\Server\Player;

use Blackjack\Game\Card;
use Blackjack\Game\DealerHand;
use Blackjack\Game\Hand;
use Blackjack\Game\Player;
use Blackjack\Server\Message\Server\ServerMessageInterface;

class RequestActionMessage implements ServerMessageInterface
{
    /** @var Player */
    private $player;

    /** @var DealerHand */
    private $dealerHand;

    /** @var Hand */
    private $playerHand;

    public function __construct(Player $player, DealerHand $dealerHand, Hand $playerHand)
    {
        $this->player     = $player;
        $this->dealerHand = $dealerHand;
        $this->playerHand = $playerHand;
    }

    public function getAlias()
    {
        return 'request_action';
    }

    private function serializeCard(Card $card)
    {
        return [
            'rank' => $card->getRank(),
            'suit' => $card->getSuit(),
        ];
    }

    private function serializeHand(Hand $hand = null)
    {
        if ($hand === null) {
            return null;
        }

        return [
            'value'        => $hand->getValue(),
            'canSplitHand' => $hand->canSplitPair(),
            'cards'        => $hand->getCards()->map(function (Card $card) {
                return $this->serializeCard($card);
            })->elements(),
        ];
    }

    public function toArray()
    {
        return [
            'dealerCard' => $this->serializeCard($this->dealerHand->getFaceUpCard()),
            'playerHand' => $this->serializeHand($this->playerHand),
            'money'      => $this->player->getChips()->getAmount(),
        ];
    }
}
