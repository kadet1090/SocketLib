<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */

namespace Kadet\SocketLib;
use Kadet\SocketLib\Utils\Event;
use Kadet\SocketLib\Utils\Logger;

/**
 * Class SocketClient
 * @package Kadet\SocketLib
 * @todo documentation
 */
class SocketClient
{
    /**
     * Server which client is connected to.
     * @var string
     */
    protected $_address;

    /**
     * Transport which client is connected by.
     * @var string
     */
    protected $_transport;

    /**
     * Port on which client is connected to server.
     * @var int
     */
    protected $_port;

    /**
     * Connection timeout.
     * @var int
     */
    protected $_timeout;

    # events
    /**
     * Event triggered when connection is successfully established.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onConnect;

    /**
     * Event triggered when client is disconnected from server.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onDisconnect;

    /**
     * Event triggered when some
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onError;

    /**
     * Event triggered when data is written to server.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onSend;

    /**
     * Event triggered when data is received to server.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onReceive;

    /**
     * Determines if client is connected to server.
     * @var bool
     */
    public $isConnected;

    /**
     * Clients sockets resource.
     * @var Resource
     */
    protected $_socket;

    /**
     * Last error data.
     * @var array
     */
    protected $_error = array(
        'string' => '',
        'code'   => 0
    );

    /**
     * Logger of clients events.
     * @var Logger
     */
    protected $_logger;

    /**
     * @param string $address   Servers address.
     * @param int    $port      Servers port.
     * @param string $transport Servers transport.
     * @param int    $timeout   Connection timeout.
     */
    public function __construct($address, $port, $transport = 'tcp', $timeout = 30)
    {
        $this->_address   = $address;
        $this->_port      = $port;
        $this->_timeout   = $timeout;
        $this->_transport = $transport;

        $this->onConnect    = new Event;
        $this->onDisconnect = new Event;
        $this->onError      = new Event;
        $this->onSend       = new Event;
        $this->onReceive    = new Event;
    }

    /**
     * Connects to specified server.
     * @param bool $blocking Blocking or not blocking mode.
     */
    public function connect($blocking = true)
    {
        $this->_socket = stream_socket_client(
            "{$this->_transport}://{$this->_address}:{$this->_port}",
            $this->_error['code'],
            $this->_error['string'],
            $this->_timeout
        );

        if (!$this->_socket)
            $this->raiseError();

        stream_set_blocking($this->_socket, $blocking);

        $this->isConnected = true;
        $this->onConnect->run();
    }

    public function disconnect()
    {
        $this->isConnected = false;
        $this->onDisconnect->run();
        stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
    }

    /**
     * Sends packet to the server.
     * @param string $text Packets content.
     */
    public function send($text)
    {
        if (!fwrite($this->_socket, $text))
            $this->raiseError();
        else
            $this->onSend->run($text);
    }

    /**
     * Receives data from server.
     * @return string
     */
    public function receive()
    {
        if(!$this->isConnected) return false;

        $result = '';
        do {
            if(($content = stream_get_contents($this->_socket)) === false) {
                $this->disconnect();
                $this->raiseError();
                return false;
            }
            $result .= $content;
        } while (!empty($content) && !empty($result));

        if(!empty($result))
            $this->onReceive->run($result);

        return trim($result);
    }

    /**
     * @throws NetworkException
     */
    private function raiseError()
    {
        $this->onError->run((int)$this->_error['code'], $this->_error['string']);
        throw new NetworkException($this->_error['string'], $this->_error['code']);
    }

    public function __destruct() {
        $this->disconnect();
    }
}