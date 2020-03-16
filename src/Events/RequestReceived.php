<?php


namespace WebRover\Pay\Events;


/**
 * Class RequestReceived
 * @package WebRover\Pay\Events
 */
class RequestReceived extends Event
{
    /**
     * Received data.
     *
     * @var array
     */
    public $data;

    /**
     * Bootstrap.
     *
     * @param string $driver
     * @param string $gateway
     * @param array $data
     */
    public function __construct($driver, $gateway, array $data)
    {
        $this->data = $data;

        parent::__construct($driver, $gateway);
    }
}
