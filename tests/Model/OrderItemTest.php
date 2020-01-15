<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\Order;
use Picqer\BolRetailer\Model\OrderItem;

class OrderItemTest extends \PHPUnit\Framework\TestCase
{
    private $order;
    private $item;

    public function setup(): void
    {
        $this->order = $this->prophesize(Order::class)->reveal();

        $this->item = new OrderItem(
            $this->order,
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/order-item.json'), true)
        );
    }

    public function testContainsOrderItemId()
    {
        $this->assertEquals('6042823871', $this->item->orderItemId);
    }

    public function testContainsOfferReference()
    {
        $this->assertEquals('MijnOffer6627', $this->item->offerReference);
    }

    public function testContainsEan()
    {
        $this->assertEquals('8785075035214', $this->item->ean);
    }

    public function testContainsTitle()
    {
        $this->assertEquals('Star Wars - The happy family 2', $this->item->title);
    }

    public function testContainsQuantity()
    {
        $this->assertEquals(3, $this->item->quantity);
    }

    public function testContainsOfferPrice()
    {
        $this->assertEquals(22.98, $this->item->offerPrice);
    }

    public function testContainsTransactionFee()
    {
        $this->assertEquals(2.22, $this->item->transactionFee);
    }

    public function testContainsLatestDeliveryDate()
    {
        $expected = \DateTime::createFromFormat('Y-m-d', '2019-05-01');

        $this->assertEquals($expected, $this->item->latestDeliveryDate);
    }

    public function testContainsOfferCondition()
    {
        $this->assertEquals('NEW', $this->item->offerCondition);
    }

    public function testContainsFulfilmentMethod()
    {
        $this->assertEquals('FBR', $this->item->fulfilmentMethod);
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
