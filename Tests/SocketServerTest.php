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


use Kadet\SocketLib\SocketServer;
use Kadet\SocketLib\Tests\Stubs\StubClient;

class SocketServerTest extends \PHPUnit_Framework_TestCase
{
    use TextProvider;

    /**
     * @var SocketServer
     */
    protected $_server;

    public function setUp()
    {
        $this->_server = new SocketServer(AF_INET, SOCK_STREAM, getprotobyname('tcp'), '127.0.0.1');
        $this->_server->blocking = false;
    }

    public function testStart()
    {
        $this->_server->start();
        $this->assertNotSame(false, @fsockopen($this->_server->address, $this->_server->port));
    }

    public function testStop()
    {
        $this->_server->start();
        usleep(5000); // some magic
        $this->_server->stop();

        $this->assertSame(false, @fsockopen($this->_server->address, $this->_server->port));
    }

    public function testRunning()
    {
        $this->assertFalse($this->_server->running);
        $this->_server->start();
        $this->assertTrue($this->_server->running);
        $this->_server->stop();
        $this->assertFalse($this->_server->running);
    }

    public function testEventOnStart()
    {
        $mock = $this->getMock('stdClass', ['test']);
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_server));

        $this->_server->onStart->add([$mock, 'test']);
        $this->_server->start();
    }

    public function testEventOnStop()
    {
        $mock = $this->getMock('stdClass', ['test']);
        $this->_server->onStop->add([$mock, 'test']);
        $this->_server->start();

        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_server));
        $this->_server->stop();
    }

    public function testEventOnClientConnects()
    {
        $mock = $this->getMock('stdClass', ['test']);
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_server), $this->isInstanceOf('Kadet\\SocketLib\\SocketServerClient'));

        $this->_server->onClientConnects->add([$mock, 'test']);
        $this->_server->start();
        $client = new StubClient('tcp://' . $this->_server->address . ':' . $this->_server->port);
        //$client->connect();

        $this->_server->handleConnections();
    }

    public function testEventOnClientDisconnects()
    {
        $mock = $this->getMock('stdClass', ['test']);

        $this->_server->onClientDisconnects->add([$mock, 'test']);
        $this->_server->start();

        $client = new StubClient('tcp://' . $this->_server->address . ':' . $this->_server->port);
        $client->connect();
        $this->_server->handleConnections();
        $client->disconnect();

        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_server), $this->isInstanceOf('Kadet\\SocketLib\\SocketServerClient'));
        $this->_server->handleConnections();
    }

    /**
     * @depends      testEventOnClientConnects
     * @dataProvider asciiProvider
     */
    public function testEventOnReceive($string)
    {
        $mock = $this->getMock('stdClass', ['test']);

        $this->_server->onReceive->add([$mock, 'test']);
        $this->_server->start();

        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_server), $this->isInstanceOf('Kadet\\SocketLib\\SocketServerClient'), $string);

        $client = new StubClient('tcp://' . $this->_server->address . ':' . $this->_server->port);
        $client->connect();

        $this->_server->handleConnections();

        $client->write($string);
        $this->_server->clients[0]->read();
        $client->disconnect();

        $this->_server->handleConnections();
    }

    /**
     * @depends      testEventOnClientConnects
     * @dataProvider asciiProvider
     */
    public function testEventOnSend($string)
    {
        $mock = $this->getMock('stdClass', ['test']);

        $this->_server->onSend->add([$mock, 'test']);
        $this->_server->start();

        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_server), $this->isInstanceOf('Kadet\\SocketLib\\SocketServerClient'), $string);

        $client = new StubClient('tcp://' . $this->_server->address . ':' . $this->_server->port);
        $client->connect();
        $this->_server->handleConnections();

        $this->_server->clients[0]->send($string);

        $client->disconnect();

        $this->_server->handleConnections();
    }

    public function testBroadcast()
    {
        $this->_server->start();

        $clients = [];
        for ($i = 0; $i < 2; $i++) {
            $client = new StubClient('tcp://' . $this->_server->address . ':' . $this->_server->port);
            $client->connect();
            $this->_server->handleConnections();
            $clients[] = $client;
        }

        $this->_server->broadcast('test');
        foreach ($clients as $client)
            $this->assertEquals('test', $client->read());
    }

    public function testBlockingMode()
    {
        $this->_server->blocking = true;

        $mock = $this->getMock('stdClass', ['connects', 'disconnects', 'receives']);
        $mock
            ->expects($this->once())
            ->method('connects')
            ->with($this->equalTo($this->_server), $this->isInstanceOf('Kadet\\SocketLib\\SocketServerClient'));

        $this->_server->onClientConnects->add([$mock, 'connects']);

        $mock
            ->expects($this->once())
            ->method('disconnects')
            ->with($this->equalTo($this->_server), $this->isInstanceOf('Kadet\\SocketLib\\SocketServerClient'));
        $this->_server->onClientDisconnects->add([$mock, 'disconnects']);

        $mock
            ->expects($this->once())
            ->method('receives')
            ->with($this->equalTo($this->_server), $this->isInstanceOf('Kadet\\SocketLib\\SocketServerClient'), 'test');
        $this->_server->onReceive->add([$mock, 'receives']);

        $this->_server->start();
        $client = new StubClient('tcp://' . $this->_server->address . ':' . $this->_server->port);

        $this->_server->onClientConnects->add(function ($server, $sc) use ($client) {
            $client->write('test');
        });

        $this->_server->onReceive->add(function ($server, $sc, $msg) use ($client) {
            $sc->send($msg);
            $this->assertEquals($msg, $client->read());
            $client->disconnect();
        });


        $client->connect();
        $this->_server->handleConnections();
    }
}
 