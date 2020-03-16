<?php


namespace WebRover\Pay\Gateways\Alipay;


use WebRover\Framework\Support\Collection;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidConfigException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Interfaces\GatewayInterface;

/**
 * Class PosGateway
 * @package WebRover\Pay\Gateways\Alipay
 */
class PosGateway implements GatewayInterface
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function pay($endpoint, array $payload)
    {
        $payload['method'] = 'alipay.trade.pay';
        $payload['biz_content'] = json_encode(array_merge(
            json_decode($payload['biz_content'], true),
            [
                'product_code' => 'FACE_TO_FACE_PAYMENT',
                'scene' => 'bar_code',
            ]
        ));
        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Alipay', 'Pos', $endpoint, $payload));

        return Support::requestApi($payload);
    }
}
