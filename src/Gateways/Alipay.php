<?php


namespace WebRover\Pay\Gateways;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WebRover\Framework\Support\Collection;
use WebRover\Framework\Support\Str;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidConfigException;
use WebRover\Pay\Exceptions\InvalidGatewayException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Gateways\Alipay\Support;
use WebRover\Pay\Interfaces\GatewayApplicationInterface;
use WebRover\Pay\Interfaces\GatewayInterface;
use WebRover\Pay\Supports\Config;

/**
 * Class Alipay
 * @package WebRover\Pay\Gateways
 * @method Response app(array $config) APP 支付
 * @method Collection pos(array $config) 刷卡支付
 * @method Collection scan(array $config) 扫码支付
 * @method Collection transfer(array $config) 帐户转账
 * @method Response wap(array $config) 手机网站支付
 * @method Response web(array $config) 电脑支付
 * @method Collection mini(array $config) 小程序支付
 */
class Alipay implements GatewayApplicationInterface
{
    /**
     * Const mode_normal.
     */
    const MODE_NORMAL = 'normal';

    /**
     * Const mode_dev.
     */
    const MODE_DEV = 'dev';

    /**
     * Const url.
     */
    const URL = [
        self::MODE_NORMAL => 'https://openapi.alipay.com/gateway.do?charset=utf-8',
        self::MODE_DEV => 'https://openapi.alipaydev.com/gateway.do?charset=utf-8',
    ];

    /**
     * Alipay payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Alipay gateway.
     *
     * @var string
     */
    protected $gateway;

    /**
     * extends.
     *
     * @var array
     */
    protected $extends;

    /**
     * Bootstrap.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->gateway = Support::create($config)->getBaseUri();
        $this->payload = [
            'app_id' => $config->get('app_id'),
            'method' => '',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'version' => '1.0',
            'return_url' => $config->get('return_url'),
            'notify_url' => $config->get('notify_url'),
            'timestamp' => date('Y-m-d H:i:s'),
            'sign' => '',
            'biz_content' => '',
            'app_auth_token' => $config->get('app_auth_token'),
        ];
    }

    /**
     * Magic pay.
     *
     * @param string $method
     * @param array $params
     *
     * @return Response|Collection
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidGatewayException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function __call($method, $params)
    {
        if (isset($this->extends[$method])) {
            return $this->makeExtend($method, ...$params);
        }

        return $this->pay($method, ...$params);
    }

    /**
     * Pay an order.
     *
     * @param string $gateway
     * @param array $params
     *
     * @return Response|Collection
     * @throws InvalidGatewayException
     */
    public function pay($gateway, $params = [])
    {
        Events::dispatch(new Events\PayStarting('Alipay', $gateway, $params));

        $this->payload['return_url'] = (isset($params['return_url']) && !is_null($params['return_url'])) ? $params['return_url'] : $this->payload['return_url'];
        $this->payload['notify_url'] = (isset($params['notify_url']) && !is_null($params['notify_url'])) ? $params['notify_url'] : $this->payload['notify_url'];

        unset($params['return_url'], $params['notify_url']);

        $this->payload['biz_content'] = json_encode($params);

        $gateway = get_class($this) . '\\' . Str::studly($gateway) . 'Gateway';

        if (class_exists($gateway)) {
            return $this->makePay($gateway);
        }

        throw new InvalidGatewayException("Pay Gateway [{$gateway}] not exists");
    }

    /**
     * Verify sign.
     *
     * @param null|array $data
     * @param bool $refund
     *
     * @return Collection
     *
     * @throws InvalidSignException
     * @throws InvalidConfigException
     */
    public function verify($data = null, $refund = false)
    {
        if (is_null($data)) {
            $request = Request::createFromGlobals();

            $data = $request->request->count() > 0 ? $request->request->all() : $request->query->all();
        }

        if (isset($data['fund_bill_list'])) {
            $data['fund_bill_list'] = htmlspecialchars_decode($data['fund_bill_list']);
        }

        Events::dispatch(new Events\RequestReceived('Alipay', '', $data));

        if (Support::verifySign($data)) {
            return new Collection($data);
        }

        Events::dispatch(new Events\SignFailed('Alipay', '', $data));

        throw new InvalidSignException('Alipay Sign Verify FAILED', $data);
    }

    /**
     * Query an order.
     *
     * @param string|array $order
     * @param string $type
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function find($order, $type = 'wap')
    {
        $gateway = get_class($this) . '\\' . Str::studly($type) . 'Gateway';

        if (!class_exists($gateway) || !is_callable([new $gateway(), 'find'])) {
            throw new GatewayException("{$gateway} Done Not Exist Or Done Not Has FIND Method");
        }

        $config = call_user_func([new $gateway(), 'find'], $order);

        $this->payload['method'] = $config['method'];
        $this->payload['biz_content'] = $config['biz_content'];
        $this->payload['sign'] = Support::generateSign($this->payload);

        Events::dispatch(new Events\MethodCalled('Alipay', 'Find', $this->gateway, $this->payload));

        return Support::requestApi($this->payload);
    }

    /**
     * Refund an order.
     *
     * @param array $order
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function refund(array $order)
    {
        $this->payload['method'] = 'alipay.trade.refund';
        $this->payload['biz_content'] = json_encode($order);
        $this->payload['sign'] = Support::generateSign($this->payload);

        Events::dispatch(new Events\MethodCalled('Alipay', 'Refund', $this->gateway, $this->payload));

        return Support::requestApi($this->payload);
    }

    /**
     * Cancel an order.
     *
     * @param array|string $order
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function cancel($order)
    {
        $this->payload['method'] = 'alipay.trade.cancel';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        $this->payload['sign'] = Support::generateSign($this->payload);

        Events::dispatch(new Events\MethodCalled('Alipay', 'Cancel', $this->gateway, $this->payload));

        return Support::requestApi($this->payload);
    }

    /**
     * Close an order.
     *
     * @param string|array $order
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function close($order)
    {
        $this->payload['method'] = 'alipay.trade.close';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        $this->payload['sign'] = Support::generateSign($this->payload);

        Events::dispatch(new Events\MethodCalled('Alipay', 'Close', $this->gateway, $this->payload));

        return Support::requestApi($this->payload);
    }

    /**
     * Download bill.
     *
     * @param string|array $bill
     *
     * @return string
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function download($bill)
    {
        $this->payload['method'] = 'alipay.data.dataservice.bill.downloadurl.query';
        $this->payload['biz_content'] = json_encode(is_array($bill) ? $bill : ['bill_type' => 'trade', 'bill_date' => $bill]);
        $this->payload['sign'] = Support::generateSign($this->payload);

        Events::dispatch(new Events\MethodCalled('Alipay', 'Download', $this->gateway, $this->payload));

        $result = Support::requestApi($this->payload);

        return ($result instanceof Collection) ? $result->get('bill_download_url') : '';
    }

    /**
     * Reply success to alipay.
     *
     * @return Response
     */
    public function success()
    {
        Events::dispatch(new Events\MethodCalled('Alipay', 'Success', $this->gateway));

        return Response::create('success');
    }

    /**
     * extend.
     *
     * @param string $method
     * @param callable $function
     * @param bool $now
     *
     * @return Collection|null
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws InvalidArgumentException
     *
     * @throws GatewayException
     */
    public function extend($method, callable $function, $now = true)
    {
        if (!$now && !method_exists($this, $method)) {
            $this->extends[$method] = $function;

            return null;
        }

        $customize = $function($this->payload);

        if (!is_array($customize) && !($customize instanceof Collection)) {
            throw new InvalidArgumentException('Return Type Must Be Array Or Collection');
        }

        Events::dispatch(new Events\MethodCalled('Alipay', 'extend', $this->gateway, $customize));

        if (is_array($customize)) {
            $this->payload = $customize;
            $this->payload['sign'] = Support::generateSign($this->payload);

            return Support::requestApi($this->payload);
        }

        return $customize;
    }

    /**
     * Make pay gateway.
     *
     * @param string $gateway
     *
     * @return Response|Collection
     * @throws InvalidGatewayException
     */
    protected function makePay($gateway)
    {
        $app = new $gateway();

        if ($app instanceof GatewayInterface) {
            return $app->pay($this->gateway, array_filter($this->payload, function ($value) {
                return $value !== '' && !is_null($value);
            }));
        }

        throw new InvalidGatewayException("Pay Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
    }

    /**
     * makeExtend.
     *
     * @param string $method
     * @param array $params
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    protected function makeExtend($method, array ...$params)
    {
        $params = count($params) >= 1 ? $params[0] : $params;

        $function = $this->extends[$method];

        $customize = $function($this->payload, $params);

        if (!is_array($customize) && !($customize instanceof Collection)) {
            throw new InvalidArgumentException('Return Type Must Be Array Or Collection');
        }

        Events::dispatch(new Events\MethodCalled(
            'Alipay',
            'extend - ' . $method,
            $this->gateway,
            is_array($customize) ? $customize : $customize->toArray()
        ));

        if (is_array($customize)) {
            $this->payload = $customize;
            $this->payload['sign'] = Support::generateSign($this->payload);

            return Support::requestApi($this->payload);
        }

        return $customize;
    }
}
