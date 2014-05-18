<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */

namespace Kadet\SocketLib;

use Kadet\SocketLib\Utils\Logger;
use Kadet\Utils\Property;

/**
 * Class SocketClient
 * @package Kadet\SocketLib
 */
class SocketClient extends AbstractClient
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

    /**
     * Determines if client is connected to server.
     * @var bool
     */
    protected $_connected = false;

    /**
     * Clients sockets resource.
     * @var Resource
     */
    protected $_socket;

    protected $_blocking;

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
    public $logger;

    /**
     * @param string $address   Servers address.
     * @param int    $port      Servers port.
     * @param string $transport Servers transport.
     * @param int    $timeout   Connection timeout.
     */
    public function __construct($address, $port, $transport = 'tcp', $timeout = 10)
    {
        parent::__construct();

        $this->_address   = $address;
        $this->_port      = $port;
        $this->_timeout   = $timeout;
        $this->_transport = $transport;
    }

    /**
     * Connects to specified source.
     *
     * @param bool $blocking Blocking or not blocking mode.
     */
    public function connect($blocking = true)
    {
        $this->_socket = @stream_socket_client(
            "{$this->_transport}://{$this->_address}:{$this->_port}",
            $this->_error['code'],
            $this->_error['string'],
            $this->_timeout
        );

        if (!$this->_socket)
            $this->raiseError();

        stream_set_blocking($this->_socket, 0);

        $this->_blocking  = $blocking;
        $this->_connected = true;
        $this->onConnect->run($this);
    }

    public function disconnect()
    {
        stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);

        $this->_connected = false;
        $this->onDisconnect->run($this);
    }

    /**
     * Sends packet to the server.
     *
     * @param string $text Packets content.
     */
    public function send($text)
    {
        if (!@fwrite($this->_socket, $text))
            $this->raiseError();
        else
            $this->onSend->run($this, $text);
    }

    /**
     * Receives data from server.
     * @return string
     */
    public function receive()
    {
        if (!$this->_connected) return false;

        $result = null;
        $start  = microtime(true);
        do {
            if (($content = stream_get_contents($this->_socket)) === false) {
                $this->disconnect();
                $this->raiseError();

                return false;
            }
            $result .= $content;

            if (microtime(true) - $start > $this->_timeout) return $result;
        } while (
            ($this->_blocking && empty($result)) ||
            (!$this->_blocking && !empty($content) && !empty($result))
        );

        if (!empty($result)) {
            $this->onReceive->run($this, $result);
        }

        return trim($result);
    }

    /**
     * @throws NetworkException
     */
    protected function raiseError()
    {
        $this->onError->run($this, (int)$this->_error['code'], $this->_error['string']);
        throw new NetworkException($this->_error['string'], $this->_error['code']);
    }

    public function __destruct()
    {
        if ($this->_connected) $this->disconnect();
    }

    public function _get_connected()
    {
        return $this->_connected;
    }

    public function _set_blocking($blocking)
    {
        $this->_blocking = (bool)$blocking;
    }

    public function _set_timeout($timeout)
    {
        $this->_timeout = intval($timeout);
    }
}