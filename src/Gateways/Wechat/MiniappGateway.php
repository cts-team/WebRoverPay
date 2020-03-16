<?php


namespace WebRover\Pay\Gateways\Wechat;


use WebRover\Framework\Support\Collection;
use WebRover\Pay\Exceptions\GatewayException;
use WebRover\Pay\Exceptions\InvalidArgumentException;
use WebRover\Pay\Exceptions\InvalidSignException;
use WebRover\Pay\Gateways\Wechat;

/**
 * Class MiniappGateway
 * @package WebRover\Pay\Gateways\Wechat
 */
class MiniappGateway extends MpGateway
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     */
    public function pay($endpoint, array $payload)
    {
        $payload['appid'] = Support::getInstance()->miniapp_id;

        if ($this->mode === Wechat::MODE_SERVICE) {
            $payload['sub_appid'] = Support::getInstance()->sub_miniapp_id;
            $this->payRequestUseSubAppId = true;
        }

        return parent::pay($endpoint, $payload);
    }
}
