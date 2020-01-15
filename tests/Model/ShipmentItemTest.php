<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\Shipment;
use Picqer\BolRetailer\Model\ShipmentItem;

class ShipmentItemTest extends \PHPUnit\Framework\TestCase
{
    private $item;
    private $shipment;

    public function setup(): void
    {
        $this->shipment = $this->prophesize(Shipment::class)->reveal();
        $this->item     = new ShipmentItem(
            $this->shipment,
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/shipment-item.json'), true)
        );
    }

    public function testContainsOrderDate()
    {
        $expected = \DateTime::createFromFormat(\DateTime::ATOM, '2018-04-20T10:58:39+02:00');

        $this->assertEquals($expected, $this->item->orderDate);
    }

    public function testContainsLatestDeliveryDate()
    {
        $expected = \DateTime::createFromFormat(\DateTime::ATOM, '2018-04-21T00:00:00+02:00');

        $this->assertEquals($expected, $this->item->latestDeliveryDate);
    }

    public function testContainsShipment()
    {
        $this->assertEquals($this->shipment, $this->item->shipment);
    }
}
