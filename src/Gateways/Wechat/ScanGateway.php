<?php


namespace WebRover\Pay\Gateways\Wechat;


use Symfony\Component\HttpFoundation\Request;
use WebRover\Framework\Support\Collection;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;

/**
 * Class ScanGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class ScanGateway extends Gateway
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
        $payload['spbill_create_ip'] = Request::createFromGlobals()->server->get('SERVER_ADDR');
        $payload['trade_type'] = $this->getTradeType();

        Events::dispatch(new Events\PayStarted('Wechat', 'Scan', $endpoint, $payload));

        return $this->preOrder($payload);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'NATIVE';
    }
}
