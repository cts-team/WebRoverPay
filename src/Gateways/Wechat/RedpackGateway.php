<?php


namespace WebRover\Pay\Gateways\Wechat;


use Symfony\Component\HttpFoundation\Request;
use WebRover\Framework\Support\Collection;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Gateways\Wechat;

/**
 * Class RedpackGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class RedpackGateway extends Gateway
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
        $payload['wxappid'] = $payload['appid'];

        if (php_sapi_name() !== 'cli') {
            $payload['client_ip'] = Request::createFromGlobals()->server->get('SERVER_ADDR');
        }

        if ($this->mode === Wechat::MODE_SERVICE) {
            $payload['msgappid'] = $payload['appid'];
        }

        unset($payload['appid'], $payload['trade_type'],
            $payload['notify_url'], $payload['spbill_create_ip']);

        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Wechat', 'Redpack', $endpoint, $payload));

        return Support::requestApi(
            'mmpaymkttransfers/sendredpack',
            $payload,
            true
        );
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }
}
