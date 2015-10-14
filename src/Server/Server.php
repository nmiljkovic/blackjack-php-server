<?php

namespace Blackjack\Server;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\Server as SocketServer;
use React\Socket\Connection as SocketConnection;

class Server
{
    /** @var LoopInterface */
    private $loop;

    /** @var ConnectionManager */
    private $connectionManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoopInterface $loop, ConnectionManager $connectionManager, LoggerInterface $logger)
    {
        $this->loop              = $loop;
        $this->connectionManager = $connectionManager;
        $this->logger            = $logger;
    }

    public function listen($port)
    {
        $server = new SocketServer($this->loop);
        $server->on('connection', function (SocketConnection $connection) {
            try {
                $this->connectionManager->accept($connection);
            } catch (\Exception $e) {
                $this->logger->error('Connection failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        });

        $server->listen($port, '0.0.0.0');
        $this->logger->info('Started socket server', [
            'port' => $port,
        ]);
    }
}
