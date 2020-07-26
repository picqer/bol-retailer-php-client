<?php

namespace Picqer\BolRetailer\Model;

use DateTime;

/**
 * @property string $orderItemId
 * @property string $orderId
 * @property DateTime|null $orderDate
 * @property DateTime|null $latestDeliveryDate
 * @property string $ean
 * @property string $title
 * @property int $quantity
 * @property float $offerPrice
 * @property string $offerReference
 * @property string $offerCondition
 * @property string $fulfilmentMethod
 * @property Shipment $shipment
 */
class ShipmentItem extends AbstractModel
{
    /** @var Shipment */
    private $shipment;

    /**
     * Constructor.
     *
     * @param Shipment $shipment The shipment the shipment item belongs to.
     * @param array $data The data of the shipment item model.
     */
    public function __construct(Shipment $shipment, array $data = [])
    {
        parent::__construct($data);

        $this->shipment = $shipment;
    }

    protected function getShipment(): Shipment
    {
        return $this->shipment;
    }

    protected function getOrderDate(): ?DateTime
    {
        if (empty($this->data['orderDate'])) {
            return null;
        }

        return DateTime::createFromFormat(DateTime::ATOM, $this->data['orderDate']);
    }

    protected function getLatestDeliveryDate(): ?DateTime
    {
        if (empty($this->data['latestDeliveryDate'])) {
            return null;
        }

        return DateTime::createFromFormat(DateTime::ATOM, $this->data['latestDeliveryDate']);
    }
}
