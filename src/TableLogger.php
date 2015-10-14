<?php

namespace Blackjack;

use Blackjack\Game\Table;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class TableLogger extends AbstractLogger
{
    /** @var Table */
    private $table;

    /** @var LoggerInterface */
    private $logger;

    use LoggerTrait;

    public function __construct(LoggerInterface $logger, Table $table)
    {
        $this->logger = $logger;
        $this->table  = $table;
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $message, array_merge($context, [
            'table' => $this->table->getId(),
        ]));
    }
}
