<?php


namespace WebRover\Pay\Exceptions;


/**
 * Class InvalidSignException
 * @package WebRover\Pay\Exceptions
 */
class InvalidSignException extends Exception
{
    /**
     * Bootstrap.
     *
     * @param string $message
     * @param array|string $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_SIGN: ' . $message, $raw, self::INVALID_SIGN);
    }
}
