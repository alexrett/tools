<?php

namespace Tools\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class MonologLoggerFactory
{
    const LINE_FORMAT = " [%datetime%] %level_name%: %message%\n";
    const DATE_FORMAT = 'Y-m-d H:i:s';

    private $options;
    private $loggers;
    private $formatter;

    /**
     * @param array $options
     */
    public function __construct( $options = [] )
    {
        $this->options = $options;
        $this->loggers = [];
        $this->formatter = new LineFormatter(self::LINE_FORMAT, self::DATE_FORMAT);
    }

    /**
     * Creates logger instance.
     *
     * @param string $name
     *
     * @return LoggerInterface
     *
     * @throws \RuntimeException
     */
    public function create($name)
    {

        if (isset($this->options['prefix']) && !empty($this->options['prefix'])) {
            $name = $this->options['prefix'] . '-' . $name;
        }
        if (isset($this->loggers[$name])) {
            return $this->loggers[$name];
        }

        $handlers = array();

        $handlers[] = $this->initRotatingFileHandler($name);

        return $this->loggers[$name] = new Logger($name, $handlers);
    }

    /**
     * @param string $option
     * @param array  $options
     *
     * @throws \InvalidArgumentException
     */
    private function checkRequiredOption($option, array $options)
    {
        if (!array_key_exists($option, $options)) {
            throw new \InvalidArgumentException(sprintf('MonologLoggerFactory: Required option "%s" not exists', $option));
        }
    }

    /**
     * @return StreamHandler
     */
    private function initStreamHandler()
    {
        $this->checkRequiredOption('level', $this->options);
        $level = (int) $this->options['level'];

        $name = 'error';
        $streamHandler = new StreamHandler(
            rtrim($this->options['log_dir'], '/') . '/' . $name . '.log',
            $level
        );

        $streamHandler->setFormatter($this->formatter);

        return $streamHandler;
    }

    /**
     * @param $name
     *
     * @return RotatingFileHandler
     */
    private function initRotatingFileHandler($name)
    {
        $this->checkRequiredOption('level', $this->options);
        $level = (int) $this->options['level'];

        $rotatingFileHandler = new RotatingFileHandler(rtrim($this->options['log_dir'], '/') . '/' . $name . '.log', 7, $level);
        $rotatingFileHandler->setFormatter($this->formatter);

        return $rotatingFileHandler;
    }
}
