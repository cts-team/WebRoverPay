<?php


namespace WebRover\Pay\Gateways\Alipay;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\InvalidConfigException;
use WebRover\Pay\Interfaces\GatewayInterface;

/**
 * Class WebGateway
 * @package WebRover\Pay\Gateways\Alipay
 */
class WebGateway implements GatewayInterface
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
        $biz_array = json_decode($payload['biz_content'], true);
        $biz_array['product_code'] = $this->getProductCode();

        $method = (isset($biz_array['http_method']) && !is_null($biz_array['http_method'])) ? $biz_array['http_method'] : 'POST';

        unset($biz_array['http_method']);

        $payload['method'] = $this->getMethod();
        $payload['biz_content'] = json_encode($biz_array);
        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Alipay', 'Web/Wap', $endpoint, $payload));

        return $this->buildPayHtml($endpoint, $payload, $method);
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
            'method' => 'alipay.trade.query',
            'biz_content' => json_encode(is_array($order) ? $order : ['out_trade_no' => $order]),
        ];
    }

    /**
     * Build Html response.
     *
     * @param string $endpoint
     * @param array $payload
     * @param string $method
     *
     * @return Response
     */
    protected function buildPayHtml($endpoint, $payload, $method = 'POST')
    {
        if (strtoupper($method) === 'GET') {
            return RedirectResponse::create($endpoint . '&' . http_build_query($payload));
        }

        $sHtml = "<form id='alipay_submit' name='alipay_submit' action='" . $endpoint . "' method='" . $method . "'>";
        foreach ($payload as $key => $val) {
            $val = str_replace("'", '&apos;', $val);
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['alipay_submit'].submit();</script>";

        return Response::create($sHtml);
    }

    /**
     * Get method config.
     *
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.trade.page.pay';
    }

    /**
     * Get productCode config.
     *
     * @return string
     */
    protected function getProductCode()
    {
        return 'FAST_INSTANT_TRADE_PAY';
    }
}
