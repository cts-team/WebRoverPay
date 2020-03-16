<?php


namespace WebRover\Pay\Gateways\Alipay;


use WebRover\Framework\Support\Collection;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidConfigException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Interfaces\GatewayInterface;

/**
 * Class MiniGateway
 * @package WebRover\Pay\Gateways\Alipay
 */
class MiniGateway implements GatewayInterface
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     * @link https://docs.alipay.com/mini/introduce/pay
     */
    public function pay($endpoint, array $payload)
    {
        if (empty(json_decode($payload['biz_content'], true)['buyer_id'])) {
            throw new InvalidArgumentException('buyer_id required');
        }

        $payload['method'] = 'alipay.trade.create';
        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Alipay', 'Mini', $endpoint, $payload));

        return Support::requestApi($payload);
    }
}
