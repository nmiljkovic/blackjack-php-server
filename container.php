<?php

use Blackjack\Server\ConnectionManager;
use Blackjack\Server\Server;
use Blackjack\Server\WebSocketManager;

$container = new Pimple\Container([
    'socket_server_settings' => [
        'max_connections' => 500,
    ],
]);

$container['loop'] = function () {
    $loop = \React\EventLoop\Factory::create();

    return $loop;
};

$container['logger'] = function () {
    return new \Blackjack\ConsoleLogger();
};

$container['table_manager'] = function ($container) {
    return new \Blackjack\Server\TableManager(
        $container['logger'],
        new \Blackjack\Server\TableLoopFactory($container),
        $container['player_factory']
    );
};

$container['table_loop_factory'] = new \Blackjack\Server\TableLoopFactory($container);
$container['player_factory']     = new \Blackjack\Server\Game\PlayerFactory($container);

$container['serializer']   = new \Blackjack\Server\Message\Serializer();
$container['deserializer'] = new \Blackjack\Server\Message\Deserializer();

$container['websocket_manager'] = function ($container) {
    return new WebSocketManager($container['table_manager'], $container['logger']);
};

$container['connection_manager'] = function ($container) {
    return new ConnectionManager(
        $container['loop'],
        $container['serializer'],
        $container['deserializer'],
        $container['table_manager'],
        $container['websocket_manager'],
        $container['player_factory'],
        $container['logger'],
        $container['socket_server_settings']['max_connections']
    );
};

$container['socket_server'] = function ($container) {
    return new Server($container['loop'], $container['connection_manager'], $container['logger']);
};

$container['websocket_server'] = function ($container) {
    $server = new \Ratchet\WebSocket\WsServer($container['websocket_manager']);
    $server->disableVersion(0);

    return $server;
};
