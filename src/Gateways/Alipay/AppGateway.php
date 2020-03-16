<?php


namespace WebRover\Pay\Gateways\Alipay;


use Symfony\Component\HttpFoundation\Response;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\InvalidConfigException;
use WebRover\Pay\Interfaces\GatewayInterface;

/**
 * Class AppGateway
 * @package WebRover\Pay\Gateways\Alipay
 */
class AppGateway implements GatewayInterface
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Response
     * @throws InvalidConfigException
     */
    public function pay($endpoint, array $payload)
    {
        $payload['method'] = 'alipay.trade.app.pay';
        $payload['biz_content'] = json_encode(array_merge(
            json_decode($payload['biz_content'], true),
            ['product_code' => 'QUICK_MSECURITY_PAY']
        ));
        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Alipay', 'App', $endpoint, $payload));

        return Response::create(http_build_query($payload));
    }
}
