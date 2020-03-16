<?php


namespace WebRover\Pay\Gateways\Wechat;


use Exception;
use WebRover\Framework\Support\Collection;
use WebRover\Framework\Support\Str;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\BusinessException;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Gateways\Wechat;
use WebRover\Pay\Log;
use WebRover\Pay\Supports\Config;
use WebRover\Pay\Supports\HasHttpRequest;


/**
 * Class Support
 * @package WebRover\Pay\Gateways\Wechat
 * @property string appid
 * @property string app_id
 * @property string miniapp_id
 * @property string sub_appid
 * @property string sub_app_id
 * @property string sub_miniapp_id
 * @property string mch_id
 * @property string sub_mch_id
 * @property string key
 * @property string return_url
 * @property string cert_client
 * @property string cert_key
 * @property array log
 * @property array http
 * @property string mode
 */
class Support
{
    use HasHttpRequest;

    /**
     * Wechat gateway.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Instance.
     *
     * @var Support
     */
    private static $instance;

    /**
     * Bootstrap.
     *
     * @param Config $config
     * @author yansongda <me@yansongda.cn>
     *
     */
    private function __construct(Config $config)
    {
        $this->baseUri = Wechat::URL[$config->get('mode', Wechat::MODE_NORMAL)];
        $this->config = $config;

        $this->setHttpOptions();
    }

    /**
     * __get.
     *
     * @param $key
     *
     * @return mixed|null|Config
     */
    public function __get($key)
    {
        return $this->getConfig($key);
    }

    /**
     * create.
     *
     * @param Config $config
     *
     * @return Support
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public static function create(Config $config)
    {
        if (php_sapi_name() === 'cli' || !(self::$instance instanceof self)) {
            self::$instance = new self($config);

            self::setDevKey();
        }

        return self::$instance;
    }

    /**
     * getInstance.
     *
     * @return Support
     * @throws InvalidArgumentException
     *
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            throw new InvalidArgumentException('You Should [Create] First Before Using');
        }

        return self::$instance;
    }

    /**
     * clear.
     *
     * @return void
     */
    public static function clear()
    {
        self::$instance = null;
    }

    /**
     * Request wechat api.
     *
     * @param string $endpoint
     * @param array $data
     * @param bool $cert
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public static function requestApi($endpoint, $data, $cert = false)
    {
        Events::dispatch(new Events\ApiRequesting('Wechat', '', self::$instance->getBaseUri() . $endpoint, $data));

        $result = self::$instance->post(
            $endpoint,
            self::toXml($data),
            $cert ? [
                'cert' => self::$instance->cert_client,
                'ssl_key' => self::$instance->cert_key,
            ] : []
        );
        $result = is_array($result) ? $result : self::fromXml($result);

        Events::dispatch(new Events\ApiRequested('Wechat', '', self::$instance->getBaseUri() . $endpoint, $result));

        return self::processingApiResult($endpoint, $result);
    }

    /**
     * Filter payload.
     *
     * @param array $payload
     * @param array|string $params
     * @param bool $preserve_notify_url
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public static function filterPayload($payload, $params, $preserve_notify_url = false)
    {
        $type = self::getTypeName((isset($params['type']) && !is_null($params['type'])) ? $params['type'] : '');

        $payload = array_merge(
            $payload,
            is_array($params) ? $params : ['out_trade_no' => $params]
        );
        $payload['appid'] = self::$instance->getConfig($type, '');

        if (self::$instance->getConfig('mode', Wechat::MODE_NORMAL) === Wechat::MODE_SERVICE) {
            $payload['sub_appid'] = self::$instance->getConfig('sub_' . $type, '');
        }

        unset($payload['trade_type'], $payload['type']);
        if (!$preserve_notify_url) {
            unset($payload['notify_url']);
        }

        $payload['sign'] = self::generateSign($payload);

        return $payload;
    }

    /**
     * Generate wechat sign.
     *
     * @param array $data
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public static function generateSign($data)
    {
        $key = self::$instance->key;

        if (is_null($key)) {
            throw new InvalidArgumentException('Missing Wechat Config -- [key]');
        }

        ksort($data);

        $string = md5(self::getSignContent($data) . '&key=' . $key);

        Log::debug('Wechat Generate Sign Before UPPER', [$data, $string]);

        return strtoupper($string);
    }

    /**
     * Generate sign content.
     *
     * @param array $data
     *
     * @return string
     */
    public static function getSignContent($data)
    {
        $buff = '';

        foreach ($data as $k => $v) {
            $buff .= ($k != 'sign' && $v != '' && !is_array($v)) ? $k . '=' . $v . '&' : '';
        }

        Log::debug('Wechat Generate Sign Content Before Trim', [$data, $buff]);

        return trim($buff, '&');
    }

    /**
     * Decrypt refund contents.
     *
     * @param string $contents
     *
     * @return string
     */
    public static function decryptRefundContents($contents)
    {
        return openssl_decrypt(
            base64_decode($contents),
            'AES-256-ECB',
            md5(self::$instance->key),
            OPENSSL_RAW_DATA
        );
    }

    /**
     * Convert array to xml.
     *
     * @param array $data
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public static function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new InvalidArgumentException('Convert To Xml Error! Invalid Array!');
        }

        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= is_numeric($val) ? '<' . $key . '>' . $val . '</' . $key . '>' :
                '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * Convert xml to array.
     *
     * @param string $xml
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public static function fromXml($xml)
    {
        if (!$xml) {
            throw new InvalidArgumentException('Convert To Array Error! Invalid Xml!');
        }

        libxml_disable_entity_loader(true);

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * Get service config.
     *
     * @param null|string $key
     * @param null|mixed $default
     *
     * @return mixed|null
     */
    public function getConfig($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->config->all();
        }

        if ($this->config->has($key)) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * Get app id according to param type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getTypeName($type = '')
    {
        switch ($type) {
            case '':
                $type = 'app_id';
                break;
            case 'app':
                $type = 'appid';
                break;
            default:
                $type = $type . '_id';
        }

        return $type;
    }

    /**
     * Get Base Uri.
     *
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * processingApiResult.
     *
     * @param       $endpoint
     * @param array $result
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    protected static function processingApiResult($endpoint, array $result)
    {
        if (!isset($result['return_code']) || $result['return_code'] != 'SUCCESS') {
            throw new GatewayException(
                'Get Wechat API Error:' . ((isset($result['return_msg']) && !is_null($result['return_msg'])) ? $result['return_msg'] : ((isset($result['retmsg']) && !is_null($result['retmsg'])) ? $result['retmsg'] : '')),
                $result
            );
        }

        if (isset($result['result_code']) && $result['result_code'] != 'SUCCESS') {
            throw new BusinessException(
                'Wechat Business Error: ' . $result['err_code'] . ' - ' . $result['err_code_des'],
                $result
            );
        }

        if ($endpoint === 'pay/getsignkey' ||
            strpos($endpoint, 'mmpaymkttransfers') !== false ||
            self::generateSign($result) === $result['sign']) {
            return new Collection($result);
        }

        Events::dispatch(new Events\SignFailed('Wechat', '', $result));

        throw new InvalidSignException('Wechat Sign Verify FAILED', $result);
    }

    /**
     * setDevKey.
     *
     * @return Support
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     * @throws Exception
     *
     * @throws GatewayException
     */
    private static function setDevKey()
    {
        if (self::$instance->mode == Wechat::MODE_DEV) {
            $data = [
                'mch_id' => self::$instance->mch_id,
                'nonce_str' => Str::random(),
            ];
            $data['sign'] = self::generateSign($data);

            $result = self::requestApi('pay/getsignkey', $data);

            self::$instance->config->offsetSet('key', $result['sandbox_signkey']);
        }

        return self::$instance;
    }

    /**
     * Set Http options.
     *
     * @return self
     */
    private function setHttpOptions()
    {
        if ($this->config->has('http') && is_array($this->config->get('http'))) {
            $this->config->forget('http.base_uri');
            $this->httpOptions = $this->config->get('http');
        }

        return $this;
    }
}
