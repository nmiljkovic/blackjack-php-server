<?php

namespace Blackjack\Server;

use Blackjack\Game\Table;
use Blackjack\Game\TableSeat;
use Blackjack\Server\Game\PlayerFactory;
use Blackjack\Server\Game\WebSocketObservableTable;
use Icecave\Collections\Vector;
use Psr\Log\LoggerInterface;

class TableManager
{
    /** @var Vector|Table[] */
    private $tables;

    /** @var \SplObjectStorage */
    private $tableLoopMap;

    /** @var LoggerInterface */
    private $logger;

    /** @var TableLoopFactory */
    private $tableLoopFactory;

    /** @var PlayerFactory */
    private $playerFactory;

    public function __construct(LoggerInterface $logger, TableLoopFactory $tableLoopFactory, PlayerFactory $playerFactory)
    {
        $this->logger           = $logger;
        $this->tableLoopFactory = $tableLoopFactory;
        $this->playerFactory    = $playerFactory;
        $this->tableLoopMap     = new \SplObjectStorage();

        $this->tables = new Vector();
    }

    /**
     * Finds a free seat or a seat occupied by a server bot in the existing pool of tables.
     * If no seats are found, creates a new table.
     *
     * @return TableSeat
     */
    public function findOrCreateVacantSeat()
    {
        foreach ($this->tables as $table) {
            foreach ($table->getSeats() as $seat) {
                if ($seat->isFree() || $seat->isServerBot()) {
                    return $seat;
                }
            }
        }

        // No free seats.
        $table = $this->createTable();

        return $table->getSeats()[0];
    }

    /**
     * Create a table with a specific number of seats.
     * All seats are assigned a computer player until a real player joins.
     *
     * @param int $seats
     *
     * @return Table
     */
    public function createTable($seats = 5)
    {
        $table = new WebSocketObservableTable($seats);

        $index = 0;
        foreach ($table->getSeats() as $seat) {
            $player = $this->playerFactory->newServerPlayer('Bot'.$index++, $seat, 5000);
            $seat->setPlayer($player);
        }

        $this->logger->info('Created new table', ['id' => $table->getId()]);

        $loop = $this->tableLoopFactory->createLoop($table);
        $this->tableLoopMap->attach($table, $loop);

        $this->tables->pushBack($table);

        return $table;
    }

    public function closeTable(Table $table)
    {
        $tableLoop = $this->getTableLoop($table);
        if ($tableLoop) {
            $tableLoop->cancel();
        }

        $this->logger->info('Closing table', ['id' => $table->getId()]);

        $this->tables = $this->tables->filter(function (Table $t) use ($table) {
            return $t !== $table;
        });
    }

    /**
     * Get all created tables.
     *
     * @return Vector|Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Get table game loop object, null if not found.
     *
     * @param Table $table
     *
     * @return TableLoop|null
     */
    public function getTableLoop(Table $table)
    {
        return $this->tableLoopMap->offsetGet($table);
    }

    /**
     * Get table by id, null if table is not found.
     *
     * @param string $id
     *
     * @return Table
     */
    public function getTableById($id)
    {
        foreach ($this->tables as $table) {
            if ($table->getId() === $id) {
                return $table;
            }
        }

        return null;
    }
}
