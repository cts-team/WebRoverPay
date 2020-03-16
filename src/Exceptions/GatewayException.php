<?php


namespace WebRover\Pay\Exceptions;


/**
 * Class GatewayException
 * @package WebRover\Pay\Exceptions
 */
class GatewayException extends Exception
{
    /**
     * Bootstrap.
     *
     * @param string $message
     * @param array|string $raw
     * @param int $code
     */
    public function __construct($message, $raw = [], $code = self::ERROR_GATEWAY)
    {
        parent::__construct('ERROR_GATEWAY: ' . $message, $raw, $code);
    }
}
