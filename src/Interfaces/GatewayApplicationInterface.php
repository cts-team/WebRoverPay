<?php


namespace WebRover\Pay\Interfaces;


use Symfony\Component\HttpFoundation\Response;
use WebRover\Framework\Support\Collection;

/**
 * Interface GatewayApplicationInterface
 * @package WebRover\Pay\Interfaces
 */
interface GatewayApplicationInterface
{
    /**
     * To pay.
     *
     * @param string $gateway
     * @param array $params
     *
     * @return Collection|Response
     */
    public function pay($gateway, $params);

    /**
     * Query an order.
     *
     * @param string|array $order
     * @param string $type
     *
     * @return Collection
     */
    public function find($order, $type);

    /**
     * Refund an order.
     *
     * @param array $order
     *
     * @return Collection
     */
    public function refund(array $order);

    /**
     * Cancel an order.
     *
     * @param string|array $order
     *
     * @return Collection
     */
    public function cancel($order);

    /**
     * Close an order.
     *
     * @param string|array $order
     *
     * @return Collection
     */
    public function close($order);

    /**
     * Verify a request.
     *
     * @param string|array|null $content
     * @param bool $refund
     *
     * @return Collection
     */
    public function verify($content, $refund);

    /**
     * Echo success to server.
     *
     * @return Response
     */
    public function success();
}
