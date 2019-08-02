<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\Order;
use Picqer\BolRetailer\Model\OrderItem;
use Picqer\BolRetailer\Model\OrderCustomerDetails;

class OrderTest extends \PHPUnit\Framework\TestCase
{
    private $order;

    public function setup()
    {
        $this->order = new Order(
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/order.json'), true)
        );
    }

    public function testContainsOrderId()
    {
        $this->assertEquals('1043965710', $this->order->orderId);
    }

    public function testContainsOrderPlacedAt()
    {
        $expected = \DateTime::createFromFormat(\DateTime::ATOM, '2019-04-30T19:56:39+02:00');

        $this->assertEquals($expected, $this->order->orderPlacedAt);
    }

    public function testContainsCustomerDetails()
    {
        $this->assertInstanceOf(OrderCustomerDetails::class, $this->order->customerDetails);
    }

    public function testContainsOrderItems()
    {
        $this->assertCount(1, $this->order->orderItems);
        $this->assertInstanceOf(OrderItem::class, $this->order->orderItems[0]);
    }
}
