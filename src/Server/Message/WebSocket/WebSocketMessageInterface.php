<?php

namespace Blackjack\Server\Message\WebSocket;

interface WebSocketMessageInterface
{
    /**
     * @return string
     */
    public function getAlias();

    /**
     * @return array
     */
    public function toArray();
}
