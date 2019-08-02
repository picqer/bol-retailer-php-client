<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\ReducedOrder;
use Picqer\BolRetailer\Model\ReducedOrderItem;

class ReducedOrderItemTest extends \PHPUnit\Framework\TestCase
{
    private $item;

    public function setup()
    {
        $this->order = $this->prophesize(ReducedOrder::class)->reveal();

        $this->item = new ReducedOrderItem(
            $this->order,
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/reduced-order-item.json'), true)
        );
    }

    public function testContainsOrderItemId()
    {
        $this->assertEquals('6042823871', $this->item->orderItemId);
    }

    public function testContainsEan()
    {
        $this->assertEquals('8785075035214', $this->item->ean);
    }

    public function testContainsQuantity()
    {
        $this->assertEquals(3, $this->item->quantity);
    }

    public function testContainsOrder()
    {
        $this->assertEquals($this->order, $this->item->order);
    }

    public function testIsCancelled()
    {
        $this->assertFalse($this->item->cancelRequest);
    }
}
