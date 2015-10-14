<?php

namespace Blackjack\Server;

use Blackjack\Game\Table;

class TableLoopFactory
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function createLoop(Table $table)
    {
        return new TableLoop(
            $this->container['connection_manager'],
            $this->container['logger'],
            $table
        );
    }
}
