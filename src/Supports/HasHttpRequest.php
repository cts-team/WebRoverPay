<?php


namespace WebRover\Pay\Supports;


use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait HasHttpRequest
 * @package WebRover\Pay\Supports
 * @property string $baseUri
 * @property float $timeout
 * @property float $connectTimeout
 */
trait HasHttpRequest
{
    /**
     * Http client.
     *
     * @var Client|null
     */
    protected $httpClient = null;

    /**
     * Http client options.
     *
     * @var array
     */
    protected $httpOptions = [];

    /**
     * Send a GET request.
     *
     * @param string $endpoint
     * @param array $query
     * @param array $headers
     *
     * @return array|string
     */
    public function get($endpoint, $query = [], $headers = [])
    {
        return $this->request('get', $endpoint, [
            'headers' => $headers,
            'query' => $query,
        ]);
    }

    /**
     * Send a POST request.
     *
     * @param string $endpoint
     * @param string|array $data
     * @param array $options
     *
     * @return array|string
     */
    public function post($endpoint, $data, $options = [])
    {
        if (!is_array($data)) {
            $options['body'] = $data;
        } else {
            $options['form_params'] = $data;
        }

        return $this->request('post', $endpoint, $options);
    }

    /**
     * Send request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     *
     * @return array|string
     */
    public function request($method, $endpoint, $options = [])
    {
        return $this->unwrapResponse($this->getHttpClient()->{$method}($endpoint, $options));
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setHttpClient(Client $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * @return Client|null
     */
    public function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = $this->getDefaultHttpClient();
        }

        return $this->httpClient;
    }

    /**
     * @return Client
     */
    public function getDefaultHttpClient()
    {
        return new Client($this->getOptions());
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setBaseUri($url)
    {
        if (property_exists($this, 'baseUri')) {
            $parsedUrl = parse_url($url);

            $this->baseUri = $parsedUrl['scheme'] . '://' .
                $parsedUrl['host'] . (isset($parsedUrl['port']) ? (':' . $parsedUrl['port']) : '');
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUri()
    {
        return property_exists($this, 'baseUri') ? $this->baseUri : '';
    }

    /**
     * @return float
     */
    public function getTimeout()
    {
        return property_exists($this, 'timeout') ? $this->timeout : 5.0;
    }

    /**
     * @param float $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        if (property_exists($this, 'timeout')) {
            $this->timeout = $timeout;
        }

        return $this;
    }

    /**
     * @return float
     */
    public function getConnectTimeout()
    {
        return property_exists($this, 'connectTimeout') ? $this->connectTimeout : 3.0;
    }

    /**
     * @param float $connectTimeout
     * @return $this
     */
    public function setConnectTimeout($connectTimeout)
    {
        if (property_exists($this, 'connectTimeout')) {
            $this->connectTimeout = $connectTimeout;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return array_merge([
            'base_uri' => $this->getBaseUri(),
            'timeout' => $this->getTimeout(),
            'connect_timeout' => $this->getConnectTimeout(),
        ], $this->getHttpOptions());
    }

    /**
     * @param array $options
     * @return HasHttpRequest
     */
    public function setOptions($options)
    {
        return $this->setHttpOptions($options);
    }

    /**
     * @return array
     */
    public function getHttpOptions()
    {
        return $this->httpOptions;
    }

    /**
     * @param array $httpOptions
     * @return $this
     */
    public function setHttpOptions(array $httpOptions)
    {
        $this->httpOptions = $httpOptions;

        return $this;
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function unwrapResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return json_decode(json_encode(simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
        }

        return $contents;
    }
}
