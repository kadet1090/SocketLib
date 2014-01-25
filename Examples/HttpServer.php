<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 24.01.14
 * Time: 22:13
 */
namespace Kadet\SocketLib\Examples;


use Kadet\SocketLib\SocketServer;
use Kadet\SocketLib\SocketServerClient;

// GET method only
class HttpServer extends SocketServer
{
    public function __construct($address, $port = 80)
    {
        parent::__construct(AF_INET, SOCK_STREAM, getprotobyname('tcp'), $address, $port);

        $this->onReceive->add([$this, '_onReceive']);
    }

    /**
     * @param SocketServer $server
     * @param SocketServerClient $client
     * @param string $request
     */
    public function _onReceive($server, $client, $request)
    {
        preg_match('/^GET (.*?) HTTP/', $request, $matches);
        $url = $matches[1];

        if (file_exists("./public_html{$url}") && !is_dir("./public_html{$url}")) {
            $content = file_get_contents("./public_html{$url}");
            $headers = [
                'HTTP/1.1 200 OK',
                'Server: SocketLibHttpServer/1.0',
                'Date: ' . date(DATE_RFC1123),
                'Content-Type: ' . mime_content_type("./public_html{$url}"),
                //'Content-Length: '.strlen($content),
                'Connection: close'
            ];
        } else {
            $content = "<h1>404 Not Found</h1>";
            $headers = [
                'HTTP/1.1 404 Not Found',
                'Server: SocketLibHttpServer/1.0',
                'Date: ' . date(DATE_RFC1123),
                'Content-Type: text/html; charset=utf-8',
                //'Content-Length: '.strlen($content),
                'Connection: close'
            ];
        }
        $client->send(implode("\r\n", $headers) . "\r\n\r\n" . $content);
        //$client->close();
    }
} 