<?php
include 'AutoLoader.php';
include 'functions.php';

$loader = new \Kadet\SocketLib\Examples\AutoLoader('Kadet\\SocketLib', '..');
$psrloader = new \Kadet\SocketLib\Examples\AutoLoader('Psr', '../Psr/');
$loader->register();
$psrloader->register();

$client = new \Kadet\SocketLib\SocketClient('localhost', 6969);
$client->onReceive->add(function ($client, $data) {
    echo $data . PHP_EOL;
});
$client->connect(false);
while (true) {
    $read = trim(fgets(STDIN));
    if ($read == 'exit') break;

    $client->send($read);
    usleep(5000);
    $client->receive();
}
$client->disconnect();