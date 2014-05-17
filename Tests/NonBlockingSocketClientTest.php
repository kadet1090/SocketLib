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

namespace Kadet\SocketLib\Tests;


use Kadet\SocketLib\SocketClient;
use Kadet\SocketLib\Tests\Stubs\StubServer;

class NonBlockingSocketClientTest extends AbstractSocketClientTest
{
    public function setUp()
    {
        $this->_server = new StubServer();
        $this->_client = new SocketClient('localhost', $this->_server->listen());

        $this->_client->connect(false);
        $this->_server->accept();
    }

    public function testNonBlocking()
    {
        $start = microtime(true);
        $this->_client->receive();
        $this->assertLessThan(5, microtime(true) - $start);
    }
} 