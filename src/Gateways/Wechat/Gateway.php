<?php


namespace WebRover\Pay\Gateways\Wechat;


use WebRover\Framework\Support\Collection;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Interfaces\GatewayInterface;

/**
 * Class Gateway
 * @package WebRover\Pay\Gateways\Wechat
 */
abstract class Gateway implements GatewayInterface
{
    /**
     * Mode.
     *
     * @var string
     */
    protected $mode;

    /**
     * Bootstrap.
     *
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        $this->mode = Support::getInstance()->mode;
    }

    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     */
    abstract public function pay($endpoint, array $payload);

    /**
     * Find.
     *
     * @param string|array $order
     *
     * @return array
     */
    public function find($order)
    {
        return [
            'endpoint' => 'pay/orderquery',
            'order' => is_array($order) ? $order : ['out_trade_no' => $order],
            'cert' => false,
        ];
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    abstract protected function getTradeType();

    /**
     * Schedule an order.
     *
     * @param array $payload
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     * @author yansongda <me@yansongda.cn>
     *
     */
    protected function preOrder($payload)
    {
        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\MethodCalled('Wechat', 'PreOrder', '', $payload));

        return Support::requestApi('pay/unifiedorder', $payload);
    }
}
