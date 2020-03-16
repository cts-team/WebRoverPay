<?php


namespace WebRover\Pay\Events;


/**
 * Class PayStarted
 * @package WebRover\Pay\Events
 */
class PayStarted extends Event
{
    /**
     * Endpoint.
     *
     * @var string
     */
    public $endpoint;

    /**
     * Payload.
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
    public function __construct($driver, $gateway, $endpoint, array $payload)
    {
        $this->endpoint = $endpoint;
        $this->payload = $payload;

        parent::__construct($driver, $gateway);
    }
}
