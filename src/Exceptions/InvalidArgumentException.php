<?php


namespace WebRover\Pay\Exceptions;


/**
 * Class InvalidArgumentException
 * @package WebRover\Pay\Exceptions
 */
class InvalidArgumentException extends Exception
{
    /**
     * Bootstrap.
     *
     * @param string $message
     * @param array|string $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_ARGUMENT: ' . $message, $raw, self::INVALID_ARGUMENT);
    }
}
