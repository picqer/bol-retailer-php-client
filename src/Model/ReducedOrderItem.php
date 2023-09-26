<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class ReducedOrderItem extends AbstractModel
{
    /**
     * Returns the definition of the model: an associative array with field names as key and
     * field definition as value. The field definition contains of
     * model: Model class or null if it is a scalar type
     * array: Boolean whether it is an array
     * @return array The model definition
     */
    public function getModelDefinition(): array
    {
        return [
            'orderItemId' => [ 'model' => null, 'array' => false ],
            'ean' => [ 'model' => null, 'array' => false ],
            'fulfilmentMethod' => [ 'model' => null, 'array' => false ],
            'fulfilmentStatus' => [ 'model' => null, 'array' => false ],
            'quantity' => [ 'model' => null, 'array' => false ],
            'quantityShipped' => [ 'model' => null, 'array' => false ],
            'quantityCancelled' => [ 'model' => null, 'array' => false ],
            'cancellationRequest' => [ 'model' => null, 'array' => false ],
            'latestChangedDateTime' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var string The id for the order item. One order can have multiple order items, but the list can only take one
     * item.
     */
    public $orderItemId;

    /**
     * @var string The EAN number associated with this product.
     */
    public $ean;

    /**
     * @var string The fulfilment method. Fulfilled by the retailer (FBR) or fulfilled by bol.com (FBB).
     */
    public $fulfilmentMethod;

    /**
     * @var string To filter on order status. You can filter on either all orders independent from their status, open
     * orders (excluding shipped and cancelled orders), and shipped orders.
     */
    public $fulfilmentStatus;

    /**
     * @var int Amount of ordered products for this order item id.
     */
    public $quantity;

    /**
     * @var int Amount of shipped products for this order item id.
     */
    public $quantityShipped;

    /**
     * @var int Amount of cancelled products for this order item id.
     */
    public $quantityCancelled;

    /**
     * @var bool Indicates whether the order was cancelled on request of the customer before the retailer has shipped
     * it.
     */
    public $cancellationRequest;

    /**
     * @var string The date and time in ISO 8601 format when the orderItem was last changed.
     */
    public $latestChangedDateTime;

    public function getLatestChangedDateTime(): ?\DateTime
    {
        if (empty($this->latestChangedDateTime)) {
            return null;
        }

        return \DateTime::createFromFormat(\DateTime::ATOM, $this->latestChangedDateTime);
    }
}
