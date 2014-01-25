<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */
namespace Kadet\SocketLib;

use Kadet\SocketLib\Utils\Event;
use Kadet\SocketLib\Utils\Logger;
use Kadet\SocketLib\Utils\Property;

/**
 * Class SocketServer
 * @package Kadet\SocketLib
 *
 * @property bool $blocking Indicates if server is blocking or not.
 * @property bool $running  Indicates if server is running or not.
 */
class SocketServer
{
    use Property;

    /**
     * Servers address
     * @var string
     */
    protected $_address;

    /**
     * Servers port
     * @var int
     */
    protected $_port;

    /**
     * Type of communication to be used by the socket.
     * @var int
     */
    protected $_type;

    /**
     * Protocol family to be used by the socket.
     * @var int
     */
    protected $_domain;

    /**
     * Sockets protocol.
     * @see getprotobyname()
     * @var
     */
    protected $_protocol;

    /**
     * Servers socket resource.
     * @var resource
     */
    protected $_socket;

    /**
     * Array with currently connected clients.
     * @var SocketServerClient[]
     */
    public $clients = [];

    /**
     * @see $blocking
     * @var bool
     */
    private $_blocking = true;

    /**
     * Logger to be used by server.
     * @var Logger
     */
    public $logger;

    /**
     * Event triggered when server has been started.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onStart;

    /**
     * Event triggered when server has been stopped.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onStop;

    /**
     * @see $running
     * @var bool
     */
    protected $_running;

    /**
     * Event triggered when new client connects to the server.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onClientConnects;

    /**
     * Event triggered when client disconnects from the server.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onClientDisconnects;

    /**
     * Event triggered when server has received message from client.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onReceive;

    /**
     * Event triggered when server has sent message to client.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onSend;

    /**
     * Event triggered when some awful error occurred.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onError;

    /**
     * @param int $domain Protocol family to be used by the socket.
     * @param int $type Type of communication to be used by the socket.
     * @param int $protocol Sockets protocol.
     * @param string $address Address on which server will be listening.
     * @param int $port Port on which server will be listening.
     */
    public function __construct($domain, $type, $protocol, $address, $port = 0)
    {
        $this->_domain = $domain;
        $this->_type = $type;
        $this->_protocol = $protocol;
        $this->_address = $address;
        $this->_port = $port;

        $this->onClientConnects = new Event;
        $this->onClientDisconnects = new Event;
        $this->onStart = new Event;
        $this->onStop = new Event;
        $this->onReceive = new Event;
        $this->onSend = new Event;
        $this->onError = new Event;

        $this->onStart->add(function ($server) {
            if (isset($this->logger)) $this->logger->info("Server has been started.");
        });
        $this->onStop->add(function ($server) {
            if (isset($this->logger)) $this->logger->info("Server has been stopped.");
        });
        $this->onClientConnects->add(function ($server, $client) {
            if (isset($this->logger)) $this->logger->info("Client {$client} has connected.");
        });
        $this->onClientDisconnects->add(function ($server, $client) {
            if (isset($this->logger)) $this->logger->info("Client {$client} has disconnected.");
        });
    }

    /**
     * Starts server.
     * @param int $backlog Max limit of queued clients.
     */
    public function start($backlog = 0)
    {
        $this->_socket = socket_create($this->_domain, $this->_type, $this->_protocol);
        socket_bind($this->_socket, $this->_address, $this->_port);
        socket_listen($this->_socket, $backlog);

        $this->onStart->run($this);
        $this->_running = true;
    }

    /**
     * Stops server.
     */
    public function stop()
    {
        $this->onStop->run($this);
        $this->_running = false;

        socket_shutdown($this->_socket);
        socket_close($this->_socket);
    }

    /**
     * Handles incoming connections.
     * Should be run on every tick of your main program loop.
     */
    public function handleConnections()
    {
        if ($this->blocking)
            $this->_blockHandle();
        else
            $this->_nonblockHandle();
    }

    public function _get_blocking()
    {
        return $this->_blocking;
    }

    public function _set_blocking($blocking)
    {
        if (!is_bool($blocking)) throw new \InvalidArgumentException('$blocking must be bool, ' . gettype($blocking) . ' given.');

        if ($blocking)
            socket_set_block($this->_socket);
        else
            socket_set_nonblock($this->_socket);

        $this->_blocking = $blocking;
    }

    public function _get_running()
    {
        return $this->_running;
    }

    public function __destruct()
    {
        if ($this->_running) $this->stop();
    }

    /**
     * Handles connections when server is in non blocking mode.
     * @internal
     */
    private function _nonblockHandle()
    {
        while ($client = @socket_accept($this->_socket)) {
            $this->clients[] = new SocketServerClient($client, $this);
            $this->onClientConnects->run($this, end($this->clients));
        }

        foreach ($this->clients as $id => $client) {
            try {
                if (get_resource_type($client->socket) != 'Socket' || $this->clients[$id]->read() === false) {
                    $this->onClientDisconnects->run($this, $this->clients[$id]);
                    unset($this->clients[$id]);
                }
            } catch (NetworkException $e) {
                $this->logger->warning($e->getMessage() . " ({$e->getCode()})");
                unset($this->clients[$id]);
            }
        }
    }

    /**
     * Handles connections when server is in blocking mode.
     * @internal
     */
    private function _blockHandle()
    {
        $client = new SocketServerClient(socket_accept($this->_socket), $this);
        $this->onClientConnects->run($this, $client);
        while ($client->read() !== false) ;
        $this->onClientDisconnects->run($this, $client);
    }
} 