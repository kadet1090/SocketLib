<?php
include '../vendor/autoload.php';
include 'functions.php';

$client = new \Kadet\SocketLib\SocketClient('localhost', 6969);
$client->onReceive->add(function ($client, $data) {
    echo $data . PHP_EOL;
});
$client->connect(false);
while (true) {
    $read = trim(fgets(STDIN));
    if ($read == 'exit') break;

    $client->send($read);
    usleep(2000);
    $client->receive();
}
$client->disconnect();