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
     * @var string
     */
    protected $_address;

    /**
     * @var string
     */
    protected $_transport;

    /**
     * @var int
     */
    protected $_port;

    /**
     * @var int
     */
    protected $_timeout;

    # events
    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onConnect;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onDisconnect;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onError;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onWrite;

    /**
     * @var \Kadet\SocketLib\Utils\Event
     */
    public $onRead;

    /**
     * @var bool
     */
    public $isConnected;

    /**
     * @var Resource
     */
    protected $_socket;

    /**
     * @var array
     */
    protected $_error = array(
        'string' => '',
        'code'   => 0
    );

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @param $address
     * @param $port
     * @param $transport
     * @param int $timeout
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
        $this->onWrite      = new Event;
        $this->onRead       = new Event;
    }

    /**
     * @param bool $blocking
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

    /**
     * @param $text string
     */
    public function write($text)
    {
        if (!fwrite($this->_socket, $text))
            $this->raiseError();
        else
            $this->onWrite->run($text);
    }

    /**
     * @return string
     */
    public function read()
    {
        $result = '';
        do {
            $content = stream_get_contents($this->_socket);
            $result .= $content;
        } while (!empty($content) && !empty($result));

        if(!empty($result))
            $this->onRead->run($result);

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
        stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
    }
}