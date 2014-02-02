<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */
namespace Kadet\SocketLib\Examples\WebSocket;

use Kadet\SocketLib\SocketServerClient;
use Kadet\Utils\Property;

/**
 * Class WebSocketServerClient
 * @package Kadet\SocketLib\Examples\WebSocket
 * @internal
 */
class WebSocketServerClient extends SocketServerClient
{
    use Property;

    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * Clients server.
     * @var WebSocketServer
     */
    protected $_server;

    /**
     * Event triggered when message is received from client.
     * @var \Kadet\Utils\Event
     */
    public $onMessage;

    private $_handshaked;

    public function _get_handshaked()
    {
        return $this->_handshaked;
    }

    public function handshake($headers)
    {
        $headers = [
            'HTTP/1.1 101 Switching Protocols',
            'Connection: Upgrade',
            'Upgrade: websocket',
            'Sec-WebSocket-Accept: ' . $this->sign(trim($headers['Sec-WebSocket-Key'])),
        ];
        parent::send(implode("\r\n", $headers) . "\r\n" . "\r\n");
        $this->_handshaked = true;
    }

    private function sign($key)
    {
        return base64_encode(sha1($key . self::GUID, true));
    }

    public function send($text, $final = true, $opcode = 0x1)
    {
        $frame = Frame::make($text, $final, $opcode);
        $test  = Frame::from($frame->data);
        parent::send($frame->data);
    }


} 