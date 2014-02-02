<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 24.01.14
 * Time: 22:13
 */
namespace Kadet\SocketLib\Examples\WebSocket;


use Kadet\SocketLib\SocketServer;
use Kadet\Utils\Event;
use Kadet\Utils\Property;

class WebSocketServer extends SocketServer
{
    use Property;

    /**
     * Event triggered when server receives message from client.
     * @var Event
     */
    public $onMessage;

    public $onInitialize;

    public $clientClass = 'Kadet\\SocketLib\\Examples\\WebSocket\\WebSocketServerClient';

    public function __construct($address, $port = 80)
    {
        $this->onMessage = new Event();
        $this->onInitialize = new Event();

        parent::__construct(AF_INET, SOCK_STREAM, getprotobyname('tcp'), $address, $port);
        $this->onReceive->add([$this, '_onReceive']);
    }

    /**
     * @param WebSocketServer       $server
     * @param WebSocketServerClient $client
     * @param string                $request
     */
    public function _onReceive($server, $client, $request)
    {
        if (!$client->handshaked) {
            $headers = $this->getHeaders($request);

            if ((int)$headers['Sec-WebSocket-Version'] != 13) {
                $client->close();
                if ($this->logger)
                    $this->logger->warning(
                        'Client {client} is using protocol version {version} which is obsolete.', [
                        'client'  => $client,
                        'version' => (int)$headers['Sec-WebSocket-Version']
                    ]);

                return;
            }

            $client->handshake($headers);
            $this->onInitialize->run($this, $client);
        } else {
            $frame = Frame::from($request);

            switch ($frame->opCode) {
                case 0x1:
                case 0x2:
                    $this->onMessage->run($this, $client, $frame);
                    break;
                case 0x8:
                    $client->close();
                    break;
                case 0x9:
                    $client->send($frame->content, true, 0xA);
                    break;
            }
        }
    }

    private function getHeaders($request)
    {
        preg_match('/^GET (.*?) HTTP/', $request, $matches);
        $resource = $matches[1];

        preg_match_all('/^([^\s]*): (.*?)$/m', $request, $matches);
        $headers             = array_combine($matches[1], $matches[2]);
        $headers['Resource'] = $resource;

        return $headers;
    }
} 