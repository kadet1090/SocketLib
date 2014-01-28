<?php
/**
 * Created by PhpStorm.
 * User: Kacper
 * Date: 26.01.14
 * Time: 17:13
 */

namespace Kadet\SocketLib\Examples\WebSocket;

// Bit by bit, pain in the ass.
/**
 * Class Frame
 * "I don't want to comment that thing"
 * @package Kadet\SocketLib\Examples\WebSocket
 */
class Frame
{
    private $first;
    private $second;
    private $start = 2;

    public $fin;
    public $rsv1;
    public $rsv2;
    public $rsv3;
    public $opCode;
    public $mask;
    public $payloadLength;
    public $maskKey = null;
    public $content;
    public $data;

    private function __construct()
    {
    }

    private function _getFin()
    {
        return (bool)$this->first[0];
    }

    private function _getRsv($no)
    {
        return (bool)$this->first[$no];
    }

    private function _getOpCode()
    {
        return bindec(substr($this->first, 4, 4));
    }

    private function _getMask()
    {
        return (bool)$this->second[0];
    }

    private function _getPayloadLength()
    {
        $len = bindec(substr($this->second, 1, 7));
        if ($len < 126) return $len;

        $arr = array_slice(str_split($this->data), 2, $len == 0x7E ? 2 : 8);
        $this->start += ($len == 0x7E ? 2 : 8);
        $len = '';
        foreach ($arr as $bit)
            $len .= decbin(ord($bit));

        return bindec($len);
    }

    private function _getMaskKey()
    {
        $key = substr($this->data, $this->start, 4);
        $this->start += 4;

        return $key;
    }

    private function _getContent()
    {
        $content = substr($this->data, $this->start);

        return $this->_applyMask($content);
    }

    private function _getData()
    {
        $data = chr(bindec($this->first)) . chr(bindec($this->second));

        $len = decbin($this->payloadLength);
        if ($this->payloadLength > 0x7D) {
            if ($this->payloadLength > 0xFFFF)
                $len = str_repeat('0', 64 - strlen($len)) . $len;
            else
                $len = str_repeat('0', 16 - strlen($len)) . $len;

            $len = str_split($len, 8);
            foreach ($len as &$byte) {
                $byte = chr(bindec($byte));
            }
            $data .= implode(null, $len);
        }
        if ($this->mask) {
            $data .= $this->maskKey;
        }

        $data .= $this->_applyMask($this->content);

        return $data;
    }

    private function _applyMask($content)
    {
        if (!$this->mask) return $content;

        $content = str_split($content);
        foreach ($content as $no => &$byte) {
            $byte = $byte ^ $this->maskKey[$no % 4];
        }

        return implode('', $content);
    }

    public static function from($data)
    {
        $frame         = new Frame();
        $frame->data   = $data;
        $frame->first  = decbin(ord($data[0]));
        $frame->second = decbin(ord($data[1]));

        $frame->fin    = $frame->_getFin();
        $frame->rsv1   = $frame->_getRsv(1);
        $frame->rsv2   = $frame->_getRsv(2);
        $frame->rsv3   = $frame->_getRsv(3);
        $frame->opCode = $frame->_getOpCode();

        $frame->mask          = $frame->_getMask();
        $frame->payloadLength = $frame->_getPayloadLength();

        if ($frame->mask) $frame->maskKey = $frame->_getMaskKey();

        $frame->content = $frame->_getContent();

        return $frame;
    }

    public static function make($content, $final = true, $opcode = 0x1)
    {
        $frame          = new Frame();
        $frame->content = $content;

        $frame->fin    = $final;
        $frame->rsv1   = false;
        $frame->rsv2   = false;
        $frame->rsv3   = false;
        $frame->opCode = $opcode;
        $frame->first  = (!$frame->fin ? '0' : '1') .
            (!$frame->rsv1 ? '0' : '1') .
            (!$frame->rsv2 ? '0' : '1') .
            (!$frame->rsv3 ? '0' : '1') .
            str_repeat('0', 4 - strlen(decbin($frame->opCode))) . decbin($frame->opCode);

        $frame->mask          = true;
        $frame->payloadLength = strlen($content);
        $paybin               = decbin($frame->payloadLength < 126 ? $frame->payloadLength : ($frame->payloadLength < 0xFFFF ? 0x7E : 0x7F));
        $frame->second        = ($frame->mask ? '1' : '0') . str_repeat('0', 7 - strlen($paybin)) . $paybin;

        if ($frame->mask) $frame->maskKey = chr(rand(32, 128)) . chr(rand(32, 128)) . chr(rand(32, 128)) . chr(rand(32, 128)); // pr0

        $frame->data = $frame->_getData();

        return $frame;
    }

    public function __toString()
    {
        return $this->content;
    }
} 