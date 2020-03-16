<?php


namespace WebRover\Pay\Events;


/**
 * Class ApiRequested
 * @package WebRover\Pay\Events
 */
class ApiRequested extends Event
{
    /**
     * Endpoint.
     *
     * @var string
     */
    public $endpoint;

    /**
     * Result.
     *
     * @var array
     */
    public $result;

    /**
     * Bootstrap.
     *
     * @param string $driver
     * @param string $gateway
     * @param string $endpoint
     * @param array $result
     */
    public function __construct($driver, $gateway, $endpoint, array $result)
    {
        $this->endpoint = $endpoint;
        $this->result = $result;

        parent::__construct($driver, $gateway);
    }
}