<?php

namespace Picqer\BolRetailer;

use Picqer\BolRetailer\Exception\UnknownResponseException;
use Picqer\BolRetailer\Model;

class Order extends Model\Order
{
    /**
     * Get a single order.
     *
     * @param string $id The identifier of the order to get.
     *
     * @return self|null
     */
    public static function get(string $id): ?Order
    {
        $response = Client::request('GET', "orders/${id}");

        $orderData = json_decode((string)$response->getBody(), true);

        if (empty($orderData)) {
            throw new UnknownResponseException();
        }

        return new Order($orderData);
    }

    /**
     * Get all open orders.
     *
     * @param int $page The page to get the orders from.
     * @param string $method The fulfilment method of the orders to list.
     *
     * @return Model\ReducedOrder[]
     */
    public static function all(int $page = 1, string $method = 'FBR'): array
    {
        $query = ['page' => $page, 'fulfilment-method' => $method];

        $response = Client::request('GET', 'orders', ['query' => $query]);
        $ordersData = json_decode((string)$response->getBody(), true);

        /** @var array<array-key, mixed> */
        $orders = $ordersData['orders'] ?? [];

        return array_map(function (array $data) {
            return new Model\ReducedOrder($data);
        }, $orders);
    }

    /**
     * Cancel an order item by order item id.
     *
     * @param string $orderItemId The id of the order item to cancel.
     * @param string $reasonCode The code representing the reason for cancellation of this item.
     *
     * @return Model\ProcessStatus
     */
    public static function cancelOrderItem(string $orderItemId, string $reasonCode): Model\ProcessStatus
    {
        $data = ['reasonCode' => $reasonCode];

        $response = Client::request('PUT', "orders/${orderItemId}/cancellation", ['body' => json_encode($data)]);

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }
}
