<?php
/**
 * Copyright (C) 2014, Some right reserved.
 * @author  Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

namespace Kadet\SocketLib;


use Kadet\Utils\Event;
use Kadet\Utils\Property;

abstract class AbstractServerClient
{
    use Property;

    /**
     * Event triggered when some awful error occurred.
     * @var \Kadet\Utils\Event
     */
    public $onError;

    /**
     * Event triggered when data is written to client.
     * @var \Kadet\Utils\Event
     */
    public $onWrite;

    /**
     * Event triggered when data is received from client.
     * @var \Kadet\Utils\Event
     */
    public $onRead;

    public function __construct()
    {
        $this->onError = new Event;
        $this->onWrite = new Event;
        $this->onRead  = new Event;

        if (!is_subclass_of(getCaller(1), 'Kadet\\SocketLib\\AbstractServer'))
            throw new \LogicException('ServerClient instances can only be created by server.');
    }

    /**
     * Sends message to client.
     *
     * @param string $data Data to be sent to client.
     */
    abstract function send($data);

    /**
     * Reads data from client.
     * @return string|bool String on success, empty if no data to be received, or false if client has disconnected.
     */
    abstract function read();

    /**
     * Closes connection with server.
     */
    abstract function close();

    /**
     * @return AbstractServer
     */
    abstract function _get_server();
} 