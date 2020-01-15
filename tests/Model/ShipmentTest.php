<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\Shipment;
use Picqer\BolRetailer\Model\ShipmentItem;

class ShipmentTest extends \PHPUnit\Framework\TestCase
{
    private $shipment;

    public function setup(): void
    {
        $this->shipment = new Shipment(
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/shipment.json'), true)
        );
    }

    public function testContainsShipmentId()
    {
        $this->assertEquals('953992381', $this->shipment->shipmentId);
    }

    public function testContainsShipmentReference()
    {
        $this->assertEquals('Shipment5', $this->shipment->shipmentReference);
    }

    public function testContainsShipmentDate()
    {
        $expected = \DateTime::createFromFormat(\DateTime::ATOM, '2018-04-20T15:10:05+02:00');

        $this->assertEquals($expected, $this->shipment->shipmentDate);
    }

    public function testContainsShipmentItems()
    {
        $this->assertCount(1, $this->shipment->shipmentItems);
        $this->assertInstanceOf(ShipmentItem::class, $this->shipment->shipmentItems[0]);
    }

    public function testContainsTransport()
    {
        $this->assertIsArray($this->shipment->transport);
    }

    public function testContainsCustomerDetails()
    {
        $this->assertIsArray($this->shipment->customerDetails);
    }
}
