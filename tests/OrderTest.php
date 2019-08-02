<?php
namespace Picqer\BolRetailer\Tests;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use GuzzleHttp\ClientInterface;
use Picqer\BolRetailer\Order;
use Picqer\BolRetailer\Client;
use Picqer\BolRetailer\Model;
use Psr\Http\Message\RequestInterface;

class OrderTest extends \PHPUnit\Framework\TestCase
{
    private $http;

    public function setup()
    {
        $this->http = $this->prophesize(ClientInterface::class);

        Client::setHttp($this->http->reveal());
    }

    public function testGetAllOrders()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-orders'));

        $this->http
            ->request('GET', 'orders', [ 'query' => [ 'page' => 1, 'fulfilment-method' => 'FBR' ]])
            ->willReturn($response);

        $orders = Order::all();

        $this->assertCount(2, $orders);
        $this->assertInstanceOf(Model\ReducedOrder::class, $orders[0]);
        $this->assertInstanceOf(Model\ReducedOrder::class, $orders[1]);
        $this->assertEquals('1043946570', $orders[0]->orderId);
        $this->assertEquals('1042831430', $orders[1]->orderId);
    }

    public function testGetAllOrdersWithPage()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-orders'));

        $this->http
            ->request('GET', 'orders', [ 'query' => [ 'page' => 2, 'fulfilment-method' => 'FBR' ]])
            ->willReturn($response);

        $orders = Order::all(2);

        $this->assertCount(2, $orders);
        $this->assertInstanceOf(Model\ReducedOrder::class, $orders[0]);
        $this->assertInstanceOf(Model\ReducedOrder::class, $orders[1]);
        $this->assertEquals('1043946570', $orders[0]->orderId);
        $this->assertEquals('1042831430', $orders[1]->orderId);
    }

    public function testGetAllOrdersWithPageAndFulfilmentMethod()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-orders'));

        $this->http
            ->request('GET', 'orders', [ 'query' => [ 'page' => 2, 'fulfilment-method' => 'FBB' ]])
            ->willReturn($response);

        $orders = Order::all(2, 'FBB');

        $this->assertCount(2, $orders);
        $this->assertInstanceOf(Model\ReducedOrder::class, $orders[0]);
        $this->assertInstanceOf(Model\ReducedOrder::class, $orders[1]);
        $this->assertEquals('1043946570', $orders[0]->orderId);
        $this->assertEquals('1042831430', $orders[1]->orderId);
    }

    public function testGetSingleOrderById()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-order'));

        $this->http
            ->request('GET', 'orders/1043946570', [])
            ->willReturn($response);

        $order = Order::get('1043946570');

        $this->assertInstanceOf(Model\Order::class, $order);
        $this->assertEquals('1043946570', $order->orderId);
    }

    /**
     * @expectedException Picqer\BolRetailer\Exception\OrderNotFoundException
     */
    public function testThrowExceptionWhenOrderNotFound()
    {
        $request   = $this->prophesize(RequestInterface::class);
        $response  = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/404-not-found'));
        $exception = new ClientException('', $request->reveal(), $response);

        $this->http
            ->request('GET', 'orders/1234', [])
            ->willThrow($exception);

        Order::get('1234');
    }
}
