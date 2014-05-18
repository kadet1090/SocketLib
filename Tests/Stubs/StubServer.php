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

class StubServer
{
    private $_client = null;
    private $_socket = null;

    public function listen()
    {
        $this->_socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if (!socket_bind($this->_socket, 'localhost', 0)) {
            throw new \RuntimeException('Could not bind to address');
        }
        socket_listen($this->_socket);
        socket_getsockname($this->_socket, $addr, $port);
        socket_set_nonblock($this->_socket);

        return $port;
    }

    public function accept()
    {
        $this->_client = @socket_accept($this->_socket);

        return $this->_client;
    }

    public function read()
    {
        $result = null;
        do {
            $data = socket_read($this->_client, 255);
            $result .= $data;
        } while (strlen($data) === 255);

        return $result;
    }

    public function write($buffer)
    {
        return socket_write($this->_client, $buffer, strlen($buffer));
    }

    public function __destruct()
    {
        if ($this->_socket) {
            socket_close($this->_socket);
        }
    }
}