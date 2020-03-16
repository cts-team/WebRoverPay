<?php


namespace WebRover\Pay\Exceptions;


/**
 * Class InvalidConfigException
 * @package WebRover\Pay\Exceptions
 */
class InvalidConfigException extends Exception
{
    /**
     * Bootstrap.
     *
     * @param string $message
     * @param array|string $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_CONFIG: ' . $message, $raw, self::INVALID_CONFIG);
    }
}
