<?php

namespace Blackjack\Server\Message\WebSocket;

use Blackjack\Game\Table;

class TableClosedMessage implements WebSocketMessageInterface
{
    /** @var Table */
    private $table;

    /**
     * TableClosedMessage constructor.
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'table_closed';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'table_id' => $this->table->getId(),
        ];
    }
}
