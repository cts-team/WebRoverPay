<?php


namespace WebRover\Pay\Supports;


use Psr\Log\LoggerInterface;

/**
 * Class Log
 * @package WebRover\Pay\Supports
 */
class Log extends Logger
{
    /**
     * instance.
     *
     * @var LoggerInterface
     */
    private static $instance;

    /**
     * Bootstrap.
     */
    private function __construct()
    {
    }

    /**
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        call_user_func_array([self::getInstance(), $method], $args);
    }

    /**
     * @param $method
     * @param $args
     */
    public static function __callStatic($method, $args)
    {
        forward_static_call_array([self::getInstance(), $method], $args);
    }

    /**
     * @return LoggerInterface|Logger
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    /**
     * @param Logger $logger
     */
    public static function setInstance(Logger $logger)
    {
        self::$instance = $logger;
    }
}
