<?php

use Monolog\Logger;

class LoggerFactory
{
    private $logger;
    private $path;

    public function __construct($stream = 'mailbox')
    {
        $this->logger = new Monolog\Logger($stream);
        $this->path = __DIR__ . '/../../log/log-' . date('d-m-Y') . '.log';
        $this->logger->pushHandler(new Monolog\Handler\StreamHandler($this->path, Logger::DEBUG));
        $this->logger->setTimezone(new DateTimeZone('Europe/Prague') );
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

}
