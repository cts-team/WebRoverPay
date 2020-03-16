<?php


namespace WebRover\Pay\Interfaces;


use Symfony\Component\HttpFoundation\Response;
use WebRover\Framework\Support\Collection;

/**
 * Interface GatewayInterface
 * @package WebRover\Pay\Interfaces
 */
interface GatewayInterface
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array  $payload
     *
     * @return Collection|Response
     */
    public function pay($endpoint, array $payload);
}
