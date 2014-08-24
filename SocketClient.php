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
 *
 * @property bool|int $encryption Used to enable/disable encryption.
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
     * Clients sockets resource.
     * @var Resource
     */
    protected $_socket;

    protected $_blocking;

    protected $_encryption = false;

    /**
     * Last error data.
     * @var array
     */
    protected $_error = array(
        'string' => '',
        'code'   => 0
    );

    protected $_connected;

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
        $this->_connected = false;
        stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
        $this->onDisconnect->run($this);
    }

    /**
     * Sends packet to the server.
     *
     * @param string $text Packets content.
     */
    public function send($text)
    {
        if (!@$this->_send($text))
            $this->raiseError();
        else
            $this->onSend->run($this, $text);
    }

    protected function _send($text)
    {
        return fwrite($this->_socket, $text);
    }

    /**
     * Receives data from server.
     * @return string
     */
    protected function _receive()
    {
        if (!$this->connected) {
            return false;
        }

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

        return trim($result);
    }

    public function receive()
    {
        $result = $this->_receive();
        if (!empty($result)) {
            $this->onReceive->run($this, $result);
        }

        return $result;
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
        if ($this->connected) {
            $this->disconnect();
        }
    }

    public function _get_connected()
    {
        if ($this->_connected && feof($this->_socket)) {
            $this->_connected = false;
            $this->onDisconnect->run($this);

            if (!empty($this->_error['code'])) {
                $this->raiseError();
            }
        }

        return $this->_connected;
    }

    public function _set_blocking($blocking)
    {
        $this->_blocking = (bool)$blocking;
    }

    public function _get_encryption()
    {
        return $this->_encryption;
    }

    public function _set_encryption($encryption)
    {
        stream_set_blocking($this->_socket, true);
        if ($encryption) {
            $encryption = $encryption === true ? STREAM_CRYPTO_METHOD_TLS_CLIENT : $encryption;

            stream_socket_enable_crypto($this->_socket, true, $encryption);
        } else {
            stream_socket_enable_crypto($this->_socket, false);
        }
        $this->_encryption = $encryption;
        stream_set_blocking($this->_socket, false);
    }

    public function _set_timeout($timeout)
    {
        $this->_timeout = intval($timeout);
    }
}