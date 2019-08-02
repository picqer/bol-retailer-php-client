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
        $parsedTimestamp = DateTime::createFromFormat(
            DateTime::ATOM,
            $this->data['shipmentDate'] ?? null
        );

        return $parsedTimestamp instanceof DateTime ? $parsedTimestamp : null;
    }

    protected function getShipmentItems(): array
    {
        return array_map(function (array $data) {
            return new ShipmentItem($this, $data);
        }, $this->data['shipmentItems'] ?? []);
    }
}
