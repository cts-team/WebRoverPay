<?php


namespace WebRover\Pay\Gateways\Alipay;


/**
 * Class RefundGateway
 * @package WebRover\Pay\Gateways\Alipay
 */
class RefundGateway
{
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
            'method' => 'alipay.trade.fastpay.refund.query',
            'biz_content' => json_encode(is_array($order) ? $order : ['out_trade_no' => $order]),
        ];
    }
}