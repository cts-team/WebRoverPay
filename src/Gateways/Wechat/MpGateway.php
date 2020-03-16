<?php


namespace WebRover\Pay\Gateways\Wechat;


use Exception;
use WebRover\Framework\Support\Collection;
use WebRover\Framework\Support\Str;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;

/**
 * Class MpGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class MpGateway extends Gateway
{
    /**
     * @var bool
     */
    protected $payRequestUseSubAppId = false;

    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     * @throws Exception
     *
     * @throws GatewayException
     */
    public function pay($endpoint, array $payload)
    {
        $payload['trade_type'] = $this->getTradeType();

        $pay_request = [
            'appId' => !$this->payRequestUseSubAppId ? $payload['appid'] : $payload['sub_appid'],
            'timeStamp' => strval(time()),
            'nonceStr' => Str::random(),
            'package' => 'prepay_id=' . $this->preOrder($payload)->get('prepay_id'),
            'signType' => 'MD5',
        ];
        $pay_request['paySign'] = Support::generateSign($pay_request);

        Events::dispatch(new Events\PayStarted('Wechat', 'JSAPI', $endpoint, $pay_request));

        return new Collection($pay_request);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'JSAPI';
    }
}
