<?php

namespace Blackjack\Server\Game;

class PlayerFactory
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function newSocketPlayer($name, $seat, $chips, $socket)
    {
        return new SocketPlayer($name, $seat, $chips, $socket, $this->container['connection_manager']);
    }

    public function newServerPlayer($name, $seat, $chips)
    {
        return new ServerPlayer($name, $seat, $chips, $this->container['connection_manager']);
    }
}
