<?php

namespace Blackjack\Server\Message\Server;

interface ServerMessageInterface
{
    public function getAlias();
    public function toArray();
}
