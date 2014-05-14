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

abstract class AbstractServer
{
    use Property;

    //<editor-fold desc="Events">
    /**
     * Event triggered when new client connects to the server.
     * @var \Kadet\Utils\Event
     */
    public $onClientConnects;

    /**
     * Event triggered when client disconnects from the server.
     * @var \Kadet\Utils\Event
     */
    public $onClientDisconnects;

    /**
     * Event triggered when server has received message from client.
     * @var \Kadet\Utils\Event
     */
    public $onReceive;

    /**
     * Event triggered when server has sent message to client.
     * @var \Kadet\Utils\Event
     */
    public $onSend;

    /**
     * Event triggered when some awful error occurred.
     * @var \Kadet\Utils\Event
     */
    public $onError;

    /**
     * Event triggered when server has been started.
     * @var \Kadet\Utils\Event
     */
    public $onStart;

    /**
     * Event triggered when server has been stopped.
     * @var \Kadet\Utils\Event
     */
    public $onStop;

    //</editor-fold>

    public function __construct()
    {
        $this->onClientConnects    = new Event;
        $this->onClientDisconnects = new Event;
        $this->onStart             = new Event;
        $this->onStop              = new Event;
        $this->onReceive           = new Event;
        $this->onSend              = new Event;
        $this->onError             = new Event;
    }

    /**
     * Starts server.
     *
     * @param int $backlog Max limit of queued clients.
     */
    abstract function start($backlog = 0);

    /**
     * Stops server.
     */
    abstract function stop();

    /**
     * Sends specified message to all connected clients.
     *
     * @param string $data Data to be sent.
     */
    abstract function broadcast($data);

    /**
     * Handles incoming connections.
     * Should be run on every tick of your main program loop.
     * You can use register_tick_function too.
     */
    abstract function handleConnections();

    abstract function _get_running();
} 