<?php


namespace WebRover\Pay\Exceptions;


/**
 * Class BusinessException
 * @package WebRover\Pay\Exceptions
 */
class BusinessException extends GatewayException
{
    /**
     * Bootstrap.
     *
     * @param string $message
     * @param array|string $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('ERROR_BUSINESS: ' . $message, $raw, self::ERROR_BUSINESS);
    }
}