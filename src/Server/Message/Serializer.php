<?php

namespace Blackjack\Server\Message;

use Blackjack\Server\Message\Server\ServerMessageInterface;

class Serializer
{
    /**
     * @param ServerMessageInterface $message
     *
     * @return string
     */
    public function serialize(ServerMessageInterface $message)
    {
        return json_encode([
            'alias' => $message->getAlias(),
            'data'  => $message->toArray(),
        ]);
    }
}
