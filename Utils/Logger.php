<?php
/**
 * Copyright 2014 Kadet <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 */
namespace Kadet\SocketLib\Utils;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
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

    private $_handles = [];

    public function __construct($file, $debugLevel = null)
    {
        if (is_string($file))
            $this->_file['default'] = $file;
        elseif (is_array($file) && isset($file['default']))
            $this->_file = $file;
        else
            throw new \InvalidArgumentException('$file needs to be string or array with at least \'default\' log defined.');
        $this->debugLevel = defined('DEBUG_LEVEL') && $debugLevel === null ? DEBUG_LEVEL : (int)$debugLevel;

        if (!file_exists(self::$directory)) mkdir(self::$directory, 0777, true);
        foreach ($this->_file as $current)
            if (!file_exists(self::$directory . '/' . $current)) touch(self::$directory . '/' . $current);
    }

    public function __destruct()
    {
        foreach ($this->_handles as $handle)
            fclose($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        if (!isset($this->_handles[$this->getPath($level)]))
            $this->_handles[$this->getPath($level)] = fopen($this->getPath($level), 'a');

        fwrite(
            $this->_handles[$this->getPath($level)],
            '[' . date('H:i:s') . '] ' . $this->interpolate($message, $context) . PHP_EOL
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = array())
    {
        echo "\033[1;31m!!! EMERGENCY " . date('H:i:s') . " !!!\033[0m" . PHP_EOL;
        echo "\033[1;31m[" . date('H:i:s') . "!!!]" . $this->interpolate($message, $context) . " \033[0m" . PHP_EOL;
        echo "\033[1;31m!!! EMERGENCY " . date('H:i:s') . " !!!\033[0m" . PHP_EOL;
        parent::emergency($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = array())
    {
        echo "\033[1;31m!!! ALERT " . date('H:i:s') . " !!!\033[0m" . PHP_EOL;
        echo "\033[1;31m" . $this->interpolate($message, $context) . " \033[0m" . PHP_EOL;
        parent::alert($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = array())
    {
        echo "\033[1;31m[" . date('H:i:s') . " #]" . $this->interpolate($message, $context) . " \033[0m" . PHP_EOL;
        parent::critical($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = array())
    {
        echo "\033[1;31m[" . date('H:i:s') . " x] \033[0m" . $this->interpolate($message, $context) . PHP_EOL;
        parent::error($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = array())
    {
        echo "\033[1;33m[" . date('H:i:s') . " !] \033[0m" . $this->interpolate($message, $context) . PHP_EOL;
        parent::warning($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = array())
    {
        echo "\033[1;32m[" . date('H:i:s') . " *] \033[0m" . $this->interpolate($message, $context) . PHP_EOL;
        parent::notice($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = array())
    {
        echo "\033[1;32m[" . date('H:i:s') . " i] \033[0m" . $this->interpolate($message, $context) . PHP_EOL;
        parent::info($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = array())
    {
        if ($this->debugLevel >= 1)
            echo "\033[1;30m[" . date('H:i:s') . " ?] \033[0m" . $this->interpolate($message, $context) . PHP_EOL;
        parent::debug($message, $context);
    }

    private function interpolate($message, $context)
    {
        $replace = [];
        foreach ($context as $key => $value)
            $replace["{{$key}}"] = $value;

        return str_replace(array_keys($replace), array_values($replace), $message);
    }

    private function getPath($level)
    {
        return self::$directory . (isset($this->_file[$level]) ? $this->_file[$level] : $this->_file['default']);
    }
}