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
 * Class SocketServerClient
 * @package Kadet\SocketLib
 * @internal
 * @todo documentation
 */
class SocketServerClient {
    /**
     * Clients IP Address
     * @var string
     */
    protected $_address;

    /**
     * Clients server.
     * @var SocketServer
     */
    protected $_server;

    /**
     * Clients socket.
     * @var Resource
     */
    public $socket;

    # events
    /**
     * Event triggered when some awful error occurred.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onError;

    /**
     * Event triggered when data is written to client.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onWrite;

    /**
     * Event triggered when data is received from client.
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onRead;

    /**
     * Logger instance, to log important, not so important messages like debug.
     * @var Logger
     */
    protected $_logger;


    /**
     * @param resource     $socket Client's socket.
     * @param SocketServer $server Client's server.
     */
    public function __construct($socket, SocketServer $server)
    {
        $this->socket  = $socket;
        $this->_server  = $server;
        socket_getpeername($this->socket, $this->_address);

        $this->onConnect    = new Event;
        $this->onDisconnect = new Event;
        $this->onError      = new Event;
        $this->onWrite      = new Event;
        $this->onRead       = new Event;
    }

    /**
     * Sends message to client.
     * @param string $text Text to send to client.
     */
    public function send($text)
    {
        $left = strlen($text);
        do {
            $written = socket_write($this->socket, $text);
            if($written === false) $this->_raiseError();
            $left -= $written;
        } while($left > 0);

        $this->onWrite->run($text);
        $this->_server->onSend->run($this, $text);

        if(isset($this->_logger)) $this->_logger->debug("Sent ".strlen($text)." bytes to {$this}: {$text}");
    }

    /**
     * Reads data from client.
     * @return string|bool String on success, empty if no data to be received, or false if client has disconnected.
     */
    public function read()
    {
        $result = '';
        do {
            $content = socket_read($this->socket, 1024);
            $result .= $content;
            if($result === '') return false;
            elseif($content === false) $this->_raiseError();
        } while (!empty($content) && !empty($result) && strlen($content) == 1024);

        if(!empty($result)) {
            $this->onRead->run($result);
            $this->_server->onReceive->run($this, $result);
            if(isset($this->_logger)) $this->_logger->debug("Received ".strlen($result)." bytes from {$this}: {$result}");
        }

        return trim($result);
    }

    /**
     * @internal
     * @throws NetworkException
     */
    private function _raiseError()
    {
        $error['code']   = socket_last_error($this->socket);
        $error['string'] = socket_strerror($error['code']);
        socket_clear_error($this->socket);

        $this->onError->run((int)$error['code'], $error['string']);
        $this->_server->onError->run((int)$error['code'], $error['string'], $this);
        throw new NetworkException($error['string'], $error['code']);
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->_address;
    }

    public function close() {
        socket_shutdown($this->socket);
        socket_close($this->socket);
    }
} 