<?php


namespace WebRover\Pay\Gateways\Wechat;


use Symfony\Component\HttpFoundation\RedirectResponse;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;

/**
 * Class WapGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class WapGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return RedirectResponse
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function pay($endpoint, array $payload)
    {
        $payload['trade_type'] = $this->getTradeType();

        Events::dispatch(new Events\PayStarted('Wechat', 'Wap', $endpoint, $payload));

        $mweb_url = $this->preOrder($payload)->get('mweb_url');

        $url = is_null(Support::getInstance()->return_url) ? $mweb_url : $mweb_url .
            '&redirect_url=' . urlencode(Support::getInstance()->return_url);

        return RedirectResponse::create($url);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'MWEB';
    }
}
