<?php

trait Logger
{
    static function getLogger() {
        return (new LoggerFactory(static::class))->getLogger();
    }
}
