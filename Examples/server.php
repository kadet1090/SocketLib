<?php
define('DEBUG_MODE', 2);

include 'AutoLoader.php';
include 'functions.php';

$loader = new \Kadet\SocketLib\Examples\AutoLoader('Kadet\\SocketLib', '..');
$psrloader = new \Kadet\SocketLib\Examples\AutoLoader('Psr', '../Psr/');
$loader->register();
$psrloader->register();

switch ($argv[1]) {
    case 'http':
        $server = new \Kadet\SocketLib\Examples\HttpServer('localhost');
        break;
    case 'echo':
        $server = new \Kadet\SocketLib\Examples\EchoServer('localhost', 6969);
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