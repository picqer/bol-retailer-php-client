<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\ReducedOrder;
use Picqer\BolRetailer\Model\ReducedOrderItem;

class ReducedOrderTest extends \PHPUnit\Framework\TestCase
{
    private $order;

    public function setup(): void
    {
        $this->order = new ReducedOrder(
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/reduced-order.json'), true)
        );
    }

    public function testContainsOrderId()
    {
        $this->assertEquals('1043946570', $this->order->orderId);
    }

    public function testContainsOrderPlacedAt()
    {
        $expected = \DateTime::createFromFormat(\DateTime::ATOM, '2019-04-29T16:18:21+02:00');

        $this->assertEquals($expected, $this->order->orderPlacedAt);
    }

    public function testContainsOrderItems()
    {
        $this->assertCount(1, $this->order->orderItems);
        $this->assertInstanceOf(ReducedOrderItem::class, $this->order->orderItems[0]);
    }
}
