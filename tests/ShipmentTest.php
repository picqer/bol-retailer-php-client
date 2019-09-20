<?php
namespace Picqer\BolRetailer\Tests;

use GuzzleHttp\Psr7;
use GuzzleHttp\ClientInterface;
use Picqer\BolRetailer\Client;
use Picqer\BolRetailer\Shipment;
use Picqer\BolRetailer\ProcessStatus;
use Picqer\BolRetailer\Model\Order;
use Picqer\BolRetailer\Model\OrderItem;
use Picqer\BolRetailer\Model\ReducedOrder;
use Picqer\BolRetailer\Model\ReducedOrderItem;

class ShipmentTest extends \PHPUnit\Framework\TestCase
{
    private $http;

    public function setup()
    {
        $this->http = $this->prophesize(ClientInterface::class);

        Client::setHttp($this->http->reveal());
    }

    public function testGetAllShipments()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-shipments'));

        $this->http
            ->request('GET', 'shipments', [ 'query' => [ 'page' => 1, 'fulfilment-method' => 'FBR' ]])
            ->willReturn($response);

        $shipments = Shipment::all();

        $this->assertCount(4, $shipments);
        $this->assertInstanceOf(Shipment::class, $shipments[0]);
        $this->assertInstanceOf(Shipment::class, $shipments[1]);
        $this->assertInstanceOf(Shipment::class, $shipments[2]);
        $this->assertInstanceOf(Shipment::class, $shipments[3]);
        $this->assertEquals('914587795', $shipments[0]->shipmentId);
        $this->assertEquals('953266576', $shipments[1]->shipmentId);
        $this->assertEquals('953267579', $shipments[2]->shipmentId);
        $this->assertEquals('953316694', $shipments[3]->shipmentId);
    }

    public function testGetAllShipmentsWithPage()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-shipments'));

        $this->http
            ->request('GET', 'shipments', [ 'query' => [ 'page' => 2, 'fulfilment-method' => 'FBR' ]])
            ->willReturn($response);

        $shipments = Shipment::all(2);

        $this->assertCount(4, $shipments);
        $this->assertInstanceOf(Shipment::class, $shipments[0]);
        $this->assertInstanceOf(Shipment::class, $shipments[1]);
        $this->assertInstanceOf(Shipment::class, $shipments[2]);
        $this->assertInstanceOf(Shipment::class, $shipments[3]);
        $this->assertEquals('914587795', $shipments[0]->shipmentId);
        $this->assertEquals('953266576', $shipments[1]->shipmentId);
        $this->assertEquals('953267579', $shipments[2]->shipmentId);
        $this->assertEquals('953316694', $shipments[3]->shipmentId);
    }

    public function testGetAllShipmentsWithPageAndOrder()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-shipments'));

        $this->http
            ->request('GET', 'shipments', [ 'query' => [ 'page' => 2, 'order-id' => "1234" ]])
            ->willReturn($response);

        $shipments = Shipment::all(2, '1234');

        $this->assertCount(4, $shipments);
        $this->assertInstanceOf(Shipment::class, $shipments[0]);
        $this->assertInstanceOf(Shipment::class, $shipments[1]);
        $this->assertInstanceOf(Shipment::class, $shipments[2]);
        $this->assertInstanceOf(Shipment::class, $shipments[3]);
        $this->assertEquals('914587795', $shipments[0]->shipmentId);
        $this->assertEquals('953266576', $shipments[1]->shipmentId);
        $this->assertEquals('953267579', $shipments[2]->shipmentId);
        $this->assertEquals('953316694', $shipments[3]->shipmentId);
    }

    public function testGetAllShipmentsWithPageAndFulfilmentMethod()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-shipments'));

        $this->http
            ->request('GET', 'shipments', [ 'query' => [ 'page' => 2, 'fulfilment-method' => 'FBB' ]])
            ->willReturn($response);

        $shipments = Shipment::all(2, null, 'FBB');

        $this->assertCount(4, $shipments);
        $this->assertInstanceOf(Shipment::class, $shipments[0]);
        $this->assertInstanceOf(Shipment::class, $shipments[1]);
        $this->assertInstanceOf(Shipment::class, $shipments[2]);
        $this->assertInstanceOf(Shipment::class, $shipments[3]);
        $this->assertEquals('914587795', $shipments[0]->shipmentId);
        $this->assertEquals('953266576', $shipments[1]->shipmentId);
        $this->assertEquals('953267579', $shipments[2]->shipmentId);
        $this->assertEquals('953316694', $shipments[3]->shipmentId);
    }

    public function testGetSingleOrderById()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-shipment'));

        $this->http
            ->request('GET', 'shipments/953992381', [])
            ->willReturn($response);

        $shipment = Shipment::get('953992381');

        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertEquals('953992381', $shipment->shipmentId);
    }

    /**
     * @dataProvider orderItemProvider
     */
    public function testCreateShipment($orderItem, string $uri)
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/202-process-status'));
        $expected = [
            'transport' => [
                'transporterCode' => 'TNT',
                'trackAndTrace' => '3SBOL0987654321'
            ]
        ];

        $this->http
            ->request('PUT', $uri, [ 'body' => json_encode($expected) ])
            ->willReturn($response);

        $processStatus = Shipment::create($orderItem, [
            'transport' => [
                'transporterCode' => 'TNT',
                'trackAndTrace' => '3SBOL0987654321'
            ]
        ]);

        $this->assertInstanceOf(ProcessStatus::class, $processStatus);
        $this->assertTrue($processStatus->isPending);
    }

    public function orderItemProvider()
    {
        $orderItem = new OrderItem(
            $this->prophesize(Order::class)->reveal(),
            [ 'orderItemId' => '1234' ]
        );

        $reducedOrderItem = new ReducedOrderItem(
            $this->prophesize(ReducedOrder::class)->reveal(),
            [ 'orderItemId' => '1234' ]
        );

        return [
            [ '1234', "orders/1234/shipment" ],
            [ $orderItem, "orders/1234/shipment" ],
            [ $reducedOrderItem, "orders/1234/shipment" ],
        ];
    }

    /**
     * @dataProvider orderProvider
     */
    public function testCreateForOrder($order, $uris)
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/202-process-status'));
        $expected = [
            'transport' => [
                'transporterCode' => 'TNT',
                'trackAndTrace' => '3SBOL0987654321'
            ]
        ];

        $this->http
            ->request('GET', "orders/1234", [])
            ->willReturn(Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-order')));

        foreach ($uris as $uri) {
            $this->http
                ->request('PUT', $uri, ['body' => json_encode($expected)])
                ->willReturn($response);
        }

        $processStatuses = Shipment::createForOrder($order, [
            'transport' => [
                'transporterCode' => 'TNT',
                'trackAndTrace' => '3SBOL0987654321'
            ]
        ]);

        $this->assertCount(1, $processStatuses);
    }

    public function orderProvider()
    {
        $order = new Order(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/json/order.json'), true)
        );

        $reducedOrder = new ReducedOrder(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/json/reduced-order.json'), true)
        );

        return [
            [ "1234", [ "orders/6107989317/shipment" ] ],
            [ $order, [ "orders/6107989317/shipment" ] ],
            [ $reducedOrder, [ "orders/6107989317/shipment" ] ],
        ];
    }
}
