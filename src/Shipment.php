<?php
namespace Picqer\BolRetailer;

use GuzzleHttp\Exception\ClientException;
use Picqer\BolRetailer\Model\Order;
use Picqer\BolRetailer\Model\ReducedOrder;
use Picqer\BolRetailer\Model\OrderItem;
use Picqer\BolRetailer\Model\ReducedOrderItem;
use Picqer\BolRetailer\Exception\HttpException;
use Picqer\BolRetailer\Exception\ShipmentNotFoundException;

final class Shipment extends Model\Shipment
{
    /**
     * Get a single shipment.
     *
     * @param string $id The identifier of the order to get.
     *
     * @return self|null
     */
    public static function get(string $id): ?Shipment
    {
        try {
            $response = Client::request('GET', "shipments/${id}");
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new self(json_decode((string) $response->getBody(), true));
    }

    /**
     * Get all existing shipments.
     *
     * @param integer $page   The page to get the shipments from.
     * @param string  $order  The order to get the shipments for.
     * @param string  $method The fulfilment method used for the orders to list the shipments for.
     *
     * @return self[]
     */
    public static function all(int $page = 1, ?string $order = null, string $method = 'FBR'): array
    {
        $query = array_filter([ 'page' => $page, 'order-id' => $order, 'fulfilment-method' => $method ]);

        try {
            $response = Client::request('GET', 'shipments', ['query' => $query]);
            $response = json_decode((string) $response->getBody(), true);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        /** @var array<array-key, mixed> */
        $shipments = $response['shipments'] ?? [];

        return array_map(function (array $data) {
            return new self($data);
        }, $shipments);
    }

    /**
     * Create a new shipment for the given order item.
     *
     * @param string|OrderItem|ReducedOrderItem $orderItem The order item to create the shipment for.
     * @param array                             $data      The data of the shipment to create.
     *
     * @return ProcessStatus
     */
    public static function create($orderItem, array $data): ProcessStatus
    {
        $orderItemId = $orderItem instanceof OrderItem || $orderItem instanceof ReducedOrderItem
            ? $orderItem->orderItemId
            : $orderItem;

        $uri = "orders/${orderItemId}/shipment";

        try {
            $response = Client::request('PUT', $uri, ['body' => json_encode($data)]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string) $response->getBody(), true));
    }

    /**
     * Create a shipment for each ordered item.
     *
     * @param string|Order|ReducedOrder $order The order to create shipments for.
     * @param array                     $data  The data of the shipment created.
     *
     * @return ProcessStatus[]
     */
    public static function createForOrder($order, array $data): array
    {
        $order = is_string($order)  ? \Picqer\BolRetailer\Order::get($order)  : $order;

        return array_map(function ($orderItem) use ($data) {
            return static::create($orderItem, $data);
        }, is_null($order) ? [] : $order->orderItems);
    }

    private static function handleException(ClientException $e): void
    {
        $response = $e->getResponse();

        if ($response && $response->getStatusCode() === 404) {
            throw new ShipmentNotFoundException(
                json_decode((string) $response->getBody(), true),
                404,
                $e
            );
        } elseif ($response) {
            throw new HttpException(
                json_decode((string) $response->getBody(), true),
                $response->getStatusCode(),
                $e
            );
        }

        throw $e;
    }
}
