<?php


namespace WebRover\Pay\Gateways\Alipay;


use WebRover\Framework\Support\Collection;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidConfigException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Interfaces\GatewayInterface;

/**
 * Class TransferGateway
 * @package WebRover\Pay\Gateways\Alipay
 */
class TransferGateway implements GatewayInterface
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
        $payload['method'] = 'alipay.fund.trans.toaccount.transfer';
        $payload['biz_content'] = json_encode(array_merge(
            json_decode($payload['biz_content'], true),
            ['product_code' => '']
        ));
        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Alipay', 'Transfer', $endpoint, $payload));

        return Support::requestApi($payload);
    }

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
            'method' => 'alipay.fund.trans.order.query',
            'biz_content' => json_encode(is_array($order) ? $order : ['out_biz_no' => $order]),
        ];
    }
}
