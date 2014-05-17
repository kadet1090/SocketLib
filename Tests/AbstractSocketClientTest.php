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
        $this->assertTrue($this->_server->accept() !== false);
    }

    /**
     * @expectedException \Kadet\SocketLib\NetworkException
     */
    public function testFailConnection()
    {
        $client = new SocketClient('hostthatdoesntexist', 1561/** because yes, or no, oh come on i don't know such things... */);
        $client->connect();
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
        $mock = $this->getMock('stdClass', ['test']);
        $mock
            ->expects($this->once())
            ->method('test')
            ->with($this->equalTo($this->_client));

        $this->_client->onConnect->add([$mock, 'test']);
        $this->_client->connect();
        $this->assertTrue($this->_server->accept() !== false);
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

    public function asciiProvider()
    {
        return [
            ['text'],
            [str_repeat('x', 1000)],
            ['jGQLVcIgnyN0y6r8o3j0butvmZPj6CLE4Wi1ymXIA1rbG2Kz4Uuv3CvAgjbwVnjrJdmGFpNPsO4ObjuPvQlCBqnugUgBifRIQmXVxYTJXyg4XErifJ4CGWtB'],
        ];
    }

    public function utf8Provider()
    {
        return [
            ['ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ'],
            ['He wonede at Ernleȝe at æðelen are chirechen'],
            ['τὸ σπίτι φτωχικὸ στὶς ἀμμουδιὲς τοῦ Ὁμήρου.'],
            ['�����������������������������'],
            ['И вдаль глядел. Пред ним широко'],
            ['ვეპხის ტყაოსანი შოთა რუსთაველი'],
            ['யாமறிந்த மொழிகளிலே தமிழ்மொழி போல்'],
            ['Mogę jeść szkło i mi nie szkodzi.']
        ];
    }
}
