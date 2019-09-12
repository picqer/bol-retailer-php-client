<?php
namespace Picqer\BolRetailer\Model;

use DateTime;

/**
 * An order.
 *
 * @property string               $orderId         The identifier of the order.
 * @property DateTime             $orderPlacedAt   The date and time the order was placed.
 * @property OrderItem[]          $orderItems      The items of the order.
 * @property OrderCustomerDetails $customerDetails The details of the customer that placed the order.
 */
class Order extends AbstractModel
{
    protected function getOrderItems(): array
    {
        /** @var array<array-key, mixed> */
        $items = $this->data['orderItems'] ?? [];

        return array_map(function (array $data) {
            return new OrderItem($this, $data);
        }, $items);
    }

    protected function getOrderPlacedAt(): ?DateTime
    {
        if (empty($this->data['dateTimeOrderPlaced'])) {
            return null;
        }

        return DateTime::createFromFormat(DateTime::ATOM, $this->data['dateTimeOrderPlaced']);
    }

    protected function getCustomerDetails(): OrderCustomerDetails
    {
        return new OrderCustomerDetails($this->data['customerDetails']);
    }
}
