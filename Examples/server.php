<?php
include '../../Utils/AutoLoader.php';
include 'functions.php';

$loader = new \Kadet\Utils\AutoLoader('Kadet', '../..');
$psrloader = new \Kadet\Utils\AutoLoader('Psr', '../Psr/');
$loader->register();
$psrloader->register();

switch ($argv[1]) {
    case 'http':
        $server = new \Kadet\SocketLib\Examples\HttpServer('localhost');
        break;
    case 'echo':
        $server = new \Kadet\SocketLib\Examples\EchoServer('localhost', 6969);
        break;
    case 'websocket':
        $server = new \Kadet\SocketLib\Examples\WebSocket\WebSocketServer('localhost', 80);
        $server->onMessage->add(function ($server, $client, $frame) {
            $server->logger->info('New message: ' . $frame->content);
            $client->send($frame->content);
        });
        break;
    default:
        die('Wrong server type given');
}

$server->logger = new \Kadet\SocketLib\Utils\Logger(['default' => 'default.log', 'debug' => 'debug.log']);
$server->start();
$server->blocking = false;
while (true) {
    $server->handleConnections();
    usleep(10000);
}