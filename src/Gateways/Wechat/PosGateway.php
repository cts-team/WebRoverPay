<?php


namespace WebRover\Pay\Gateways\Wechat;


use WebRover\Framework\Support\Collection;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;

/**
 * Class PosGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class PosGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function pay($endpoint, array $payload)
    {
        unset($payload['trade_type'], $payload['notify_url']);

        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Wechat', 'Pos', $endpoint, $payload));

        return Support::requestApi('pay/micropay', $payload);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'MICROPAY';
    }
}
