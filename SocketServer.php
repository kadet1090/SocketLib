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
 * @property bool $blocking
 * @todo documentation
 */
class SocketServer
{
    use Property;

    /**
     * Servers address
     * @var string
     */
    protected $_address;
    protected $_port;
    protected $_type;
    protected $_domain;
    protected $_protocol;

    protected $_socket;

    public $clients = [];

    private $_blocking = true;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onStart;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onStop;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onClientConnects;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onClientDisconnects;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onReceive;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onSend;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onError;

    public function __construct($domain, $type, $protocol, $address, $port = 0)
    {
        $this->_domain   = $domain;
        $this->_type     = $type;
        $this->_protocol = $protocol;
        $this->_address  = $address;
        $this->_port     = $port;
        $this->_socket   = socket_create($this->_domain, $this->_type, $this->_protocol);
        socket_bind($this->_socket, $this->_address, $this->_port);

        $this->onClientConnects    = new Event;
        $this->onClientDisconnects = new Event;
        $this->onStart             = new Event;
        $this->onStop              = new Event;
        $this->onReceive           = new Event;
        $this->onSend              = new Event;
        $this->onError             = new Event;
    }

    public function start($backlog = 0)
    {
        socket_listen($this->_socket, $backlog);
    }

    public function stop()
    {
        socket_close($this->_socket);
    }

    /**
     * Handles incoming connections.
     * Should be run on every tick of your main program loop.
     */
    public function handleConnections()
    {
        if($this->blocking)
            $this->_blockHandle();
        else
            $this->_nonblockHandle();
    }

    public function _get_blocking() {
        return $this->_blocking;
    }

    public function _set_blocking($blocking) {
        if(!is_bool($blocking)) throw new \InvalidArgumentException("Twój argument jest invalidą");

        if($blocking)
            socket_set_block($this->_socket);
        else
            socket_set_nonblock($this->_socket);

        $this->_blocking = $blocking;
    }

    public function __destruct() {
        socket_close($this->_socket);
    }

    private function _nonblockHandle() {
        $read = [$this->_socket];
        $placeholder = null;
        foreach($this->clients as $client)
            $read[] = $client->socket;

        if(socket_select($read, $placeholder, $placeholder, null) < 0)
            return;

        while($client = socket_accept($this->_socket)) {
            $this->clients[] = new SocketServerClient($client, $this);
            $this->onClientConnects->run(end($this->clients));
        }

        foreach($this->clients as $id => $client) {
            if(!in_array($client->socket, $read));

            if($this->clients[$id]->read() === false) {
                $this->onClientDisconnects->run($this->clients[$id]);
                unset($this->clients[$id]);
            }
        }
    }

    private function _blockHandle() {
        $client = new SocketServerClient(socket_accept($this->_socket), $this);
        $this->onClientConnects->run($client);
        while($client->read() !== false);
        $this->onClientDisconnects->run($client);
    }
} 