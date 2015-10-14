<?php

namespace Blackjack\Server\Message;

use Blackjack\Server\Message\Client\PlayerActionMessage;
use Blackjack\Server\Message\Client\PlayerBetMessage;
use Blackjack\Server\Message\Client\PlayerSetNameMessage;

class Deserializer
{
    public function deserialize($json)
    {
        $alias = $json['alias'];

        switch ($alias) {
            case 'bet':
                $amount = $json['data']['amount'];

                return new PlayerBetMessage($amount);
            case 'action':
                $action = $json['data']['action'];

                return new PlayerActionMessage($action);
            case 'set_name':
                $newName = $json['data']['newName'];

                return new PlayerSetNameMessage($newName);
            default:
                return null;
        }
    }
}
