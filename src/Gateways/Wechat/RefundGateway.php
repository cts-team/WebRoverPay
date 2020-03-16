<?php


namespace WebRover\Pay\Gateways\Wechat;


use WebRover\Pay\Exceptions\InvalidArgumentException;

/**
 * Class RefundGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class RefundGateway extends Gateway
{
    /**
     * Find.
     *
     * @param $order
     *
     * @return array
     */
    public function find($order)
    {
        return [
            'endpoint' => 'pay/refundquery',
            'order' => is_array($order) ? $order : ['out_trade_no' => $order],
            'cert' => false,
        ];
    }

    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return void
     * @throws InvalidArgumentException
     *
     */
    public function pay($endpoint, array $payload)
    {
        throw new InvalidArgumentException('Not Support Refund In Pay');
    }

    /**
     * Get trade type config.
     *
     * @return void
     * @throws InvalidArgumentException
     *
     */
    protected function getTradeType()
    {
        throw new InvalidArgumentException('Not Support Refund In Pay');
    }
}
