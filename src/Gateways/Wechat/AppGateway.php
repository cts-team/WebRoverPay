<?php


namespace WebRover\Pay\Gateways\Wechat;


use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use WebRover\Framework\Support\Str;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Gateways\Wechat;

/**
 * Class AppGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class AppGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Response
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     * @throws Exception
     *
     * @throws GatewayException
     */
    public function pay($endpoint, array $payload)
    {
        $payload['appid'] = Support::getInstance()->appid;
        $payload['trade_type'] = $this->getTradeType();

        if ($this->mode === Wechat::MODE_SERVICE) {
            $payload['sub_appid'] = Support::getInstance()->sub_appid;
        }

        $pay_request = [
            'appid' => $this->mode === Wechat::MODE_SERVICE ? $payload['sub_appid'] : $payload['appid'],
            'partnerid' => $this->mode === Wechat::MODE_SERVICE ? $payload['sub_mch_id'] : $payload['mch_id'],
            'prepayid' => $this->preOrder($payload)->get('prepay_id'),
            'timestamp' => strval(time()),
            'noncestr' => Str::random(),
            'package' => 'Sign=WXPay',
        ];
        $pay_request['sign'] = Support::generateSign($pay_request);

        Events::dispatch(new Events\PayStarted('Wechat', 'App', $endpoint, $pay_request));

        return JsonResponse::create($pay_request);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'APP';
    }
}
