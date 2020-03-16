<?php


namespace WebRover\Pay;


use Exception;
use WebRover\Framework\Support\Str;
use WebRover\Pay\Exceptions\InvalidGatewayException;
use WebRover\Pay\Gateways\Alipay;
use WebRover\Pay\Gateways\Wechat;
use WebRover\Pay\Interfaces\GatewayApplicationInterface;
use WebRover\Pay\Listeners\KernelLogSubscriber;
use WebRover\Pay\Supports\Config;
use WebRover\Pay\Supports\Logger;
use WebRover\Pay\Supports\Log;

/**
 * Class Pay
 * @package WebRover\Pay
 * @method static Alipay alipay(array $config) 支付宝
 * @method static Wechat wechat(array $config) 微信
 */
class Pay
{
    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Bootstrap.
     *
     * @param array $config
     *
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);

        $this->registerLogService();
        $this->registerEventService();
    }

    /**
     * Magic static call.
     *
     * @param string $method
     * @param array $params
     *
     * @return GatewayApplicationInterface
     * @throws Exception
     *
     * @throws InvalidGatewayException
     */
    public static function __callStatic($method, $params)
    {
        $app = new self(...$params);

        return $app->create($method);
    }

    /**
     * Create a instance.
     *
     * @param string $method
     *
     * @return GatewayApplicationInterface
     * @throws InvalidGatewayException
     */
    protected function create($method)
    {
        $gateway = __NAMESPACE__ . '\\Gateways\\' . Str::studly($method);

        if (class_exists($gateway)) {
            return self::make($gateway);
        }

        throw new InvalidGatewayException("Gateway [{$method}] Not Exists");
    }

    /**
     * Make a gateway.
     *
     * @param string $gateway
     *
     * @return GatewayApplicationInterface
     * @throws InvalidGatewayException
     */
    protected function make($gateway)
    {
        $app = new $gateway($this->config);

        if ($app instanceof GatewayApplicationInterface) {
            return $app;
        }

        throw new InvalidGatewayException("Gateway [{$gateway}] Must Be An Instance Of GatewayApplicationInterface");
    }

    /**
     * Register log service.
     *
     * @throws Exception
     */
    protected function registerLogService()
    {
        $config = $this->config->get('log');
        $config['identify'] = 'webrover.pay';

        $logger = new Logger();
        $logger->setConfig($config);

        Log::setInstance($logger);
    }

    /**
     * Register event service.
     *
     * @return void
     */
    protected function registerEventService()
    {
        Events::setDispatcher(Events::createDispatcher());

        Events::addSubscriber(new KernelLogSubscriber());
    }
}
