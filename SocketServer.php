<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */
namespace Kadet\SocketLib;

use Kadet\SocketLib\Utils\Logger;
use Kadet\Utils\Property;

/**
 * Class SocketServer
 * @package Kadet\SocketLib
 *
 * @property      bool   $blocking Indicates if server is blocking or not.
 * @property-read bool   $running  Indicates if server is running or not.
 * @property-read string $address  Servers address.
 * @property-read int    $port     Servers port.
 */
class SocketServer extends AbstractServer
{
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
     * @see $running
     * @var bool
     */
    protected $_running = false;

    public $clientClass = 'Kadet\\SocketLib\\SocketServerClient';

    /**
     * @param int    $domain   Protocol family to be used by the socket.
     * @param int    $type     Type of communication to be used by the socket.
     * @param int    $protocol Sockets protocol.
     * @param string $address  Address on which server will be listening.
     * @param int    $port     Port on which server will be listening.
     */
    public function __construct($domain, $type, $protocol, $address, $port = 0)
    {
        $this->_domain   = $domain;
        $this->_type     = $type;
        $this->_protocol = $protocol;
        $this->_address  = $address;
        $this->_port     = $port;

        parent::__construct(); // init events

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

    /** {@inheritdoc} */
    public function start($backlog = 0)
    {
        $this->_socket = socket_create($this->_domain, $this->_type, $this->_protocol);
        socket_bind($this->_socket, $this->_address, $this->_port);
        socket_listen($this->_socket, $backlog);

        if ($this->_blocking) {
            socket_set_block($this->_socket);
        } else {
            socket_set_nonblock($this->_socket);
        }

        $this->onStart->run($this);
        $this->_running = true;
    }

    /** {@inheritdoc} */
    public function stop()
    {
        $this->onStop->run($this);
        $this->_running = false;
        socket_close($this->_socket);
    }

    /** {@inheritdoc} */
    public function handleConnections()
    {
        if ($this->blocking) {
            $this->_blockHandle();
        } else {
            $this->_nonblockHandle();
        }
    }

    public function _get_blocking()
    {
        return $this->_blocking;
    }

    public function _set_blocking($blocking)
    {
        if (!is_bool($blocking)) throw new \InvalidArgumentException('$blocking must be bool, ' . gettype($blocking) . ' given.');
        $this->_blocking = $blocking;

        if (!$this->_running) return;
        $blocking ? socket_set_block($this->_socket) : socket_set_nonblock($this->_socket);
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
            $this->clients[] = new $this->clientClass($client, $this);
            $this->onClientConnects->run($this, end($this->clients));
        }

        foreach ($this->clients as $id => $client) {
            try {
                if (get_resource_type($client->socket) != 'Socket' || $this->clients[$id]->read() === false) {
                    $this->onClientDisconnects->run($this, $this->clients[$id]);
                    unset($this->clients[$id]);
                }
            } catch (NetworkException $e) {
                if (isset($this->logger)) $this->logger->warning($e->getMessage() . " ({$e->getCode()})");
                $this->onClientDisconnects->run($this, $this->clients[$id]);
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

    /** {@inheritdoc} */
    public function broadcast($message)
    {
        foreach ($this->clients as $client)
            $client->send($message);
    }

    public function _get_port()
    {
        if ($this->_port == 0) {
            socket_getsockname($this->_socket, $addr, $this->_port);
        }

        return $this->_port;
    }

    public function _get_address()
    {
        return $this->_address;
    }
} 