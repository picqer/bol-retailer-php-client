<?php


namespace Picqer\BolRetailerV5\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV5\Client;
use GuzzleHttp\Client as HttpClient;
use Picqer\BolRetailerV5\Model\AbstractModel;
use Picqer\BolRetailerV5\Model\OrderItem;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ClientTest extends TestCase
{

    /** @var Client */
    private $client;

    /** @var ObjectProphecy */
    private $httpProphecy;

    public function setup(): void
    {
        $this->httpProphecy = $this->prophesize(HttpClient::class);
        $this->client = new Client();
        $this->client->setHttp($this->httpProphecy->reveal());

        $this->authenticate();
    }

    protected function authenticate()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token'));

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpProphecy->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testMethodReturnsModel()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-order'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $order = $this->client->getOrder('test');
        $this->assertInstanceOf(AbstractModel::class, $order);
    }

    public function testMethodUnwrapsMonoFieldResponse()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-reduced-orders'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $reducedOrders = $this->client->getOrders();
        $this->assertIsArray($reducedOrders);
    }

    public function testMethodUnwrapsMonoFieldResponse404ToEmptyArray()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/404-not-found'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpProphecy->request(Argument::cetera())->willThrow($clientException);

        $deliveryOptions = $this->client->getDeliveryOptions([]);

        $this->assertEquals([], $deliveryOptions);
    }

    public function testMethodWrapsScalarArgumentToMonoFieldRequest()
    {
        $body = null;
        $this->httpProphecy->request('POST', Argument::any(), Argument::any())
            ->will(function ($args) use (&$body) {
                $options = $args[2];
                $body = $options['body'] ?? '';
                return Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/202-offers-export'));
            });

        $expectedBody = json_encode([
            'format' => 'CSV'
        ]);

        $this->client->postOfferExport('CSV');

        $this->assertEquals($expectedBody, $body);
    }

    public function testMethodWrapsArrayArgumentToMonoFieldRequest()
    {
        $body = null;
        $this->httpProphecy->request('POST', Argument::any(), Argument::any())
            ->will(function ($args) use (&$body) {
                $options = $args[2];
                $body = $options['body'] ?? '';
                return Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-delivery-options'));
            });

        $orderItems = array_map(function ($id) {
            $orderItem = new OrderItem();
            $orderItem->orderItemId = $id;
            return $orderItem;
        }, ['1', '2', '3']);

        $expectedBody = json_encode([
            'orderItems' => array_map(function ($id) {
                return ['orderItemId' => $id];
            }, ['1', '2', '3'])
        ]);

        $this->client->getDeliveryOptions($orderItems);

        $this->assertEquals($expectedBody, $body);
    }

    public function testMethodWithMissingFieldDueToEmptyArrayReturnsEmptyArray()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-reduced-orders-empty'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $reducedOrders = $this->client->getOrders();
        $this->assertEquals([], $reducedOrders);
    }
}
