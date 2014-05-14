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

/**
 * Class AbstractClient
 * @package Kadet\SocketLib
 *
 * @property-read bool $connected
 */
abstract class AbstractClient
{
    use Property;

    //<editor-fold desc="Events">
    /**
     * Event triggered when connection is successfully established.
     * @var \Kadet\Utils\Event
     */
    public $onConnect;

    /**
     * Event triggered when client is disconnected from server.
     * @var \Kadet\Utils\Event
     */
    public $onDisconnect;

    /**
     * Event triggered when some
     * @var \Kadet\Utils\Event
     */
    public $onError;

    /**
     * Event triggered when data is written to server.
     * @var \Kadet\Utils\Event
     */
    public $onSend;

    /**
     * Event triggered when data is received from server.
     * @var \Kadet\Utils\Event
     */
    public $onReceive;
    //</editor-fold>

    /**
     * Determines if client is connected to server.
     * @return bool
     */
    abstract public function _get_connected();

    /**
     * Connects to specified source.
     */
    abstract public function connect();

    /**
     * Disconnects from source.
     */
    abstract public function disconnect();

    /**
     * Sends data to source.
     *
     * @param string $data
     */
    abstract public function send($data);

    /**
     * Reads available data from source.
     *
     * @return string
     */
    abstract public function receive();

    public function __construct()
    {
        $this->onConnect    = new Event;
        $this->onDisconnect = new Event;
        $this->onError      = new Event;
        $this->onSend       = new Event;
        $this->onReceive    = new Event;
    }
} 