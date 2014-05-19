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

abstract class AbstractSocketClientTest extends \PHPUnit_Framework_TestCase
{
    use TextProvider;

    /**
     * @var StubServer
     */
    protected $_server;

    /**
     * @var SocketClient
     */
    protected $_client;

    public function testConnection()
    {
        $this->_client->connect();
        $this->assertNotSame(false, $this->_server->accept());
    }

    /**
     * @expectedException \Kadet\SocketLib\NetworkException
     */
    public function testFailConnection()
    {
        $client = new SocketClient('hostthatdoesntexist', 1561/** because yes, or no, oh come on i don't know such things... */);
        $client->connect();
    }

    public function testConnected()
    {
        $this->_client->connect();
        $this->assertTrue($this->_client->connected);
        $this->_client->disconnect();
        $this->assertFalse($this->_client->connected);
    }

    /**
     * @dataProvider asciiProvider
     */
    public function testAsciiWrite($string)
    {
        $this->_client->send($string);
        $this->assertEquals($string, $this->_server->read());
    }

    /**
     * @dataProvider utf8Provider
     */
    public function testUtf8Write($string)
    {
        $this->_client->send($string);
        $this->assertEquals($string, $this->_server->read());
    }

    /**
     * @dataProvider asciiProvider
     */
    public function testAsciiReceive($string)
    {
        $this->_server->write($string);
        $this->assertEquals($string, $this->_client->receive());
    }

    /**
     * @dataProvider utf8Provider
     */
    public function testUtf8Receive($string)
    {
        $this->_server->write($string);
        $this->assertEquals($string, $this->_client->receive());
    }

    public function testEventOnConnect()
    {
        echo 'travis';
        $mock = $this->getMock('stdClass', ['test']);
        echo 'travis';
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_client));
        echo 'travis';
        $this->_client->onConnect->add([$mock, 'test']);
        echo 'travis';
        $this->_client->connect();
        echo 'debugging';
        $this->assertNotSame(false, $this->_server->accept());
        echo 'kurwa';
    }

    public function testEventOnDisconnect()
    {
        $mock = $this->getMock('stdClass', ['test']);
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_client));

        $this->_client->onDisconnect->add([$mock, 'test']);
        $this->_client->disconnect();
    }

    /**
     * @dataProvider asciiProvider
     */
    public function testEventOnReceive($string)
    {
        $mock = $this->getMock('stdClass', ['test']);
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_client), $this->equalTo($string));

        $this->_client->onReceive->add([$mock, 'test']);
        $this->_server->write($string);
        $this->_client->receive();
    }

    /**
     * @dataProvider asciiProvider
     */
    public function testEventOnSend($string)
    {
        $mock = $this->getMock('stdClass', ['test']);
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_client), $this->equalTo($string));

        $this->_client->onSend->add([$mock, 'test']);
        $this->_client->send($string);
    }

    /**
     * @dataProvider asciiProvider
     * @expectedException \Kadet\SocketLib\NetworkException
     */
    public function testEventOnError($string)
    {
        $mock = $this->getMock('stdClass', ['test']);
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_client), $this->anything());

        $this->_client->onError->add([$mock, 'test']);
        $this->_client->disconnect();
        $this->_client->send($string);
    }

    public function tearDown()
    {
        unset($this->_server);
        unset($this->_client);
    }
}
