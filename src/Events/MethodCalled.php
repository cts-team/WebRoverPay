<?php


namespace WebRover\Pay\Events;


/**
 * Class MethodCalled
 * @package WebRover\Pay\Events
 */
class MethodCalled extends Event
{
    /**
     * endpoint.
     *
     * @var string
     */
    public $endpoint;

    /**
     * payload.
     *
     * @var array
     */
    public $payload;

    /**
     * Bootstrap.
     *
     * @param string $driver
     * @param string $gateway
     * @param string $endpoint
     * @param array $payload
     */
    public function __construct($driver, $gateway, $endpoint, array $payload = [])
    {
        $this->endpoint = $endpoint;
        $this->payload = $payload;

        parent::__construct($driver, $gateway);
    }
}
