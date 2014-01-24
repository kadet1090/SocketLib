<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */
namespace Kadet\SocketLib\Utils;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends  AbstractLogger
{
    /**
     * Directory where logs are stored.
     * @var string[]
     */
    public static $directory = './Logs/';

    public $debugLevel;

    protected $_file = array(
        'default' => '',
    );

    public function __construct($file, $debugLevel = null)
    {
        $this->_file['default'] = $file;
        $this->debugLevel = defined('DEBUG_LEVEL') && $debugLevel === null ? DEBUG_LEVEL : (int)$debugLevel;
        if(!file_exists(self::$directory)) mkdir(self::$directory, 0777, true);
        if(!file_exists(self::$directory.'/'.$this->_file)) touch(self::$directory.'/'.$this->_file);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        file_put_contents(
            self::$directory . (isset($this->_file[$level]) ? $this->_file[$level] : $this->_file['default']),
            '[' . date('H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = array())
    {
        echo "\033[1;31m[" . date('H:i:s') . " emergency]" . $message . " \033[0m" . PHP_EOL;
        parent::emergency($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = array())
    {
        echo "\033[1;31m!!! ALERT " . date('H:i:s') . " !!!\033[0m" . PHP_EOL;
        echo "\033[1;31m" . $message . " \033[0m" . PHP_EOL;
        parent::alert($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = array())
    {
        echo "\033[1;31m[" . date('H:i:s') . " critical]" . $message . " \033[0m" . PHP_EOL;
        parent::critical($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = array())
    {
        echo "\033[1;31m[" . date('H:i:s') . " x] \033[0m" . $message . PHP_EOL;
        parent::error($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = array())
    {
        echo "\033[1;33m[" . date('H:i:s') . " !] \033[0m" . $message . PHP_EOL;
        parent::warning($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = array())
    {
        echo "\033[1;32m[" . date('H:i:s') . " *] \033[0m" . $message . PHP_EOL;
        parent::notice($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = array())
    {
        echo "\033[1;32m[" . date('H:i:s') . " i] \033[0m" . $message . PHP_EOL;
        parent::info($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = array())
    {
        if($this->debugLevel >= 1)
            echo "\033[1;30m[" . date('H:i:s') . " ?] \033[0m" . $message . PHP_EOL;
        parent::debug($message, $context);
    }
}