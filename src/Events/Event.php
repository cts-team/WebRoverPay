<?php


namespace WebRover\Pay\Events;


use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * Class Event
 * @package WebRover\Pay\Events
 */
class Event extends SymfonyEvent
{
    /**
     * Driver.
     *
     * @var string
     */
    public $driver;

    /**
     * Method.
     *
     * @var string
     */
    public $gateway;

    /**
     * Extra attributes.
     *
     * @var mixed
     */
    public $attributes;

    /**
     * Bootstrap.
     *
     * @param string $driver
     * @param string $gateway
     */
    public function __construct($driver, $gateway)
    {
        $this->driver = $driver;
        $this->gateway = $gateway;
    }
}