<?php
namespace  Picqer\BolRetailer\Model;

use DateTime;

/**
 * @property string          $shipmentId
 * @property DateTime        $shipmentDate
 * @property string          $shipmentReference
 * @property ShipmentItem[]  $shipmentItems
 * @property array           $transport
 * @property array           $customerDetails
 */
class Shipment extends AbstractModel
{
    protected function getShipmentDate(): ?DateTime
    {
        if (empty($this->data['shipmentDate'])) {
            return null;
        }
        
        return DateTime::createFromFormat(DateTime::ATOM, $this->data['shipmentDate']);
    }

    protected function getShipmentItems(): array
    {
        /** @var array<array-key, mixed> */
        $items = $this->data['shipmentItems'] ?? [];

        return array_map(function (array $data) {
            return new ShipmentItem($this, $data);
        }, $items);
    }
}
