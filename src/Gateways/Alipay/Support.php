<?php


namespace WebRover\Pay\Gateways\Alipay;


use WebRover\Framework\Support\Collection;
use WebRover\Framework\Support\Str;
use WebRover\Pay\Events;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidConfigException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Gateways\Alipay;
use WebRover\Pay\Log;
use WebRover\Pay\Supports\Config;
use WebRover\Pay\Supports\HasHttpRequest;

class Support
{
    use HasHttpRequest;

    /**
     * Alipay gateway.
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
     */
    private function __construct(Config $config)
    {
        $this->baseUri = Alipay::URL[$config->get('mode', Alipay::MODE_NORMAL)];
        $this->config = $config;

        $this->setHttpOptions();
    }

    /**
     * __get.
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
     */
    public static function create(Config $config)
    {
        if (php_sapi_name() === 'cli' || !(self::$instance instanceof self)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * clear.
     *
     * @return void
     */
    public function clear()
    {
        self::$instance = null;
    }

    /**
     * Get Alipay API result.
     *
     * @param array $data
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     * @author yansongda <me@yansongda.cn>
     *
     */
    public static function requestApi(array $data)
    {
        Events::dispatch(new Events\ApiRequesting('Alipay', '', self::$instance->getBaseUri(), $data));

        $data = array_filter($data, function ($value) {
            return ($value == '' || is_null($value)) ? false : true;
        });

        $result = json_decode(self::$instance->post('', $data), true);

        Events::dispatch(new Events\ApiRequested('Alipay', '', self::$instance->getBaseUri(), $result));

        return self::processingApiResult($data, $result);
    }

    /**
     * Generate sign.
     *
     * @param array $params
     *
     * @return string
     * @throws InvalidConfigException
     *
     */
    public static function generateSign(array $params)
    {
        $privateKey = self::$instance->private_key;

        if (is_null($privateKey)) {
            throw new InvalidConfigException('Missing Alipay Config -- [private_key]');
        }

        if (Str::endsWith($privateKey, '.pem')) {
            $privateKey = openssl_pkey_get_private(
                Str::startsWith($privateKey, 'file://') ? $privateKey : 'file://' . $privateKey
            );
        } else {
            $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }

        openssl_sign(self::getSignContent($params), $sign, $privateKey, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($sign);

        Log::debug('Alipay Generate Sign', [$params, $sign]);

        if (is_resource($privateKey)) {
            openssl_free_key($privateKey);
        }

        return $sign;
    }

    /**
     * Verify sign.
     *
     * @param array $data
     * @param bool $sync
     * @param string|null $sign
     *
     * @return bool
     * @throws InvalidConfigException
     *
     */
    public static function verifySign(array $data, $sync = false, $sign = null)
    {
        $publicKey = self::$instance->ali_public_key;

        if (is_null($publicKey)) {
            throw new InvalidConfigException('Missing Alipay Config -- [ali_public_key]');
        }

        if (Str::endsWith($publicKey, '.pem')) {
            $publicKey = openssl_pkey_get_public(
                Str::startsWith($publicKey, 'file://') ? $publicKey : 'file://' . $publicKey
            );
        } else {
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($publicKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }

        $sign = !is_null($sign) ? $sign : $data['sign'];

        $toVerify = $sync ? json_encode($data, JSON_UNESCAPED_UNICODE) : self::getSignContent($data, true);

        $isVerify = openssl_verify($toVerify, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;

        if (is_resource($publicKey)) {
            openssl_free_key($publicKey);
        }

        return $isVerify;
    }

    /**
     * Get signContent that is to be signed.
     *
     * @param array $data
     * @param bool $verify
     *
     * @return string
     */
    public static function getSignContent(array $data, $verify = false)
    {
        ksort($data);

        $stringToBeSigned = '';
        foreach ($data as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }

        Log::debug('Alipay Generate Sign Content Before Trim', [$data, $stringToBeSigned]);

        return trim($stringToBeSigned, '&');
    }

    /**
     * Convert encoding.
     *
     * @param string|array $data
     * @param string $to
     * @param string $from
     *
     * @return array
     */
    public static function encoding($data, $to = 'utf-8', $from = 'gb2312')
    {
        return self::encode((array) $data, $to, $from);

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
     * @param $data
     * @param $result
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    protected static function processingApiResult($data, $result)
    {
        $method = str_replace('.', '_', $data['method']) . '_response';

        if (!isset($result['sign']) || $result[$method]['code'] != '10000') {
            throw new GatewayException(
                'Get Alipay API Error:' . $result[$method]['msg'] .
                (isset($result[$method]['sub_code']) ? (' - ' . $result[$method]['sub_code']) : ''),
                $result
            );
        }

        if (self::verifySign($result[$method], true, $result['sign'])) {
            return new Collection($result[$method]);
        }

        Events::dispatch(new Events\SignFailed('Alipay', '', $result));

        throw new InvalidSignException('Alipay Sign Verify FAILED', $result);
    }

    /**
     * Set Http options.
     *
     * @return self
     */
    protected function setHttpOptions()
    {
        if ($this->config->has('http') && is_array($this->config->get('http'))) {
            $this->config->forget('http.base_uri');
            $this->httpOptions = $this->config->get('http');
        }

        return $this;
    }

    public static function encode($array, $to_encoding, $from_encoding = 'gb2312')
    {
        $encoded = [];

        foreach ($array as $key => $value) {
            $encoded[$key] = is_array($value) ? self::encode($value, $to_encoding, $from_encoding) :
                mb_convert_encoding($value, $to_encoding, $from_encoding);
        }

        return $encoded;
    }
}