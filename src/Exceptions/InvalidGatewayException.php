<?php


namespace WebRover\Pay\Exceptions;


/**
 * Class InvalidGatewayException
 * @package WebRover\Pay\Exceptions
 */
class InvalidGatewayException extends Exception
{
    /**
     * Bootstrap.
     *
     * @param string $message
     * @param array|string $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_GATEWAY: ' . $message, $raw, self::INVALID_GATEWAY);
    }
}
