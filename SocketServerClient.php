<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */
namespace Kadet\SocketLib;

use Kadet\SocketLib\Utils\Logger;
use Kadet\Utils\Property;

/**
 * Class SocketServerClient
 * @package Kadet\SocketLib
 * @internal
 * @todo    documentation
 */
class SocketServerClient extends AbstractServerClient
{
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
     * Logger instance, to log important, not so important messages like debug.
     * @var Logger
     */
    public $logger;


    /**
     * @param resource     $socket Client's socket.
     * @param SocketServer $server Client's server.
     */
    public function __construct($socket, SocketServer $server)
    {
        parent::__construct();

        $this->socket  = $socket;
        $this->_server = $server;
        $this->logger  = $this->_server->logger;
        socket_getpeername($this->socket, $this->_address);
    }

    /** {@inheritdoc} */
    public function send($text)
    {
        $left = strlen($text);
        do {
            $written = @socket_write($this->socket, $text);
            if ($written === false) $this->_raiseError();
            $left -= $written;
        } while ($left > 0);

        $this->onWrite->run($this, $text);
        $this->_server->onSend->run($this->_server, $this, $text);

        if (isset($this->logger)) $this->logger->debug("Sent " . strlen($text) . " bytes to {$this}: {$text}");
    }

    /** {@inheritdoc} */
    public function read()
    {
        $result = '';
        do {
            $content = @socket_read($this->socket, 1024);
            $result .= $content;
            if ($content === '' && $result === '') return false;
            if ($content === false) $this->_raiseError();
        } while (!empty($content) && !empty($result) && strlen($content) == 1024);

        if (!empty($result)) {
            $this->onRead->run($this, $result);
            $this->_server->onReceive->run($this->_server, $this, $result);
            if (isset($this->logger)) $this->logger->debug("Received " . strlen($result) . " bytes from {$this}: {$result}");
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
        $error['string'] = trim(socket_strerror($error['code']));
        socket_clear_error($this->socket);
        if ($error['code'] == SOCKET_EINPROGRESS || $error['code'] == SOCKET_EWOULDBLOCK) return;

        $this->onError->run($this, (int)$error['code'], $error['string']);
        $this->_server->onError->run($this->_server, (int)$error['code'], $error['string'], $this);
        throw new NetworkException($error['string'], $error['code']);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->_address;
    }

    public function close()
    {
        socket_shutdown($this->socket);
        socket_close($this->socket);
    }

    /**
     * @return AbstractServer
     */
    function _get_server()
    {
        return $this->_server;
    }
}