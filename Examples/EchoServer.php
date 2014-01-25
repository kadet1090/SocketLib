<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 24.01.14
 * Time: 22:13
 */
namespace Kadet\SocketLib\Examples;


use Kadet\SocketLib\SocketServer;
use Kadet\SocketLib\SocketServerClient;

/**
 * Class EchoServer
 * @package Kadet\SocketLib\Examples
 *
 * Simple server that provides echo (resends all messages) functionality.
 */
class EchoServer extends SocketServer
{
    public function __construct($address, $port)
    {
        parent::__construct(
            AF_INET, // AF_INET so this server is based on IP protocol family
            SOCK_STREAM, // Stream socket type, which is base of...
            getprotobyname('tcp'), // ...TCP protocol
            $address, // servers address
            $port // and port
        );

        // Register event handler, it will be called when server receive data from client.
        $this->onReceive->add([$this, '_onReceive']);
    }

    /**
     * @param string $server Server which runs this function, in this case it is unnecessary ($server == $this), but with multiple servers it would make sens.
     * @param SocketServerClient $client Client from which message came.
     * @param string $message Message content.
     */
    public function _onReceive($server, $client, $message)
    {
        // Send same message to client.
        $client->send($message);
    }
} 