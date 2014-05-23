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

namespace Kadet\SocketLib\Tests\Stubs;

class StubClient
{
    private $_host = null;
    private $_socket;

    public function __construct($host)
    {
        $this->_host = $host;
    }

    public function connect()
    {
        $this->_socket = stream_socket_client($this->_host, $err, $err, 0, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT);
        stream_set_blocking($this->_socket, 0);
        sleep(1);
    }

    public function read()
    {
        $result = null;
        do {
            $data = stream_get_contents($this->_socket, 255);
            $result .= $data;
        } while (strlen($data) === 255);


        return $result;
    }

    public function write($buffer)
    {
        return @fwrite($this->_socket, $buffer, strlen($buffer));
    }

    public function disconnect()
    {
        if ($this->_socket) {
            stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}