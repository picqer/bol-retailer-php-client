<?php

namespace Picqer\BolRetailerV10\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV10\Client;
use GuzzleHttp\Client as HttpClient;
use Picqer\BolRetailerV10\Model\AbstractModel;
use Picqer\BolRetailerV10\Model\OrderItem;

class ClientTest extends TestCase
{

    /** @var Client */
    private $client;

    /** @var HttpClient */
    private $httpClientMock;

    public function setup(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        $this->client = new Client();
        $this->client->setHttp($this->httpClientMock);

        $this->authenticateByClientCredentials();
    }

    protected function authenticateByClientCredentials()
    {
        $rawResponse = file_get_contents(__DIR__ . '/Fixtures/http/200-token');

        $response = Message::parseResponse($rawResponse);

        $httpClientMock = $this->createMock(HttpClient::class);

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $httpClientMock->method('request')->with('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        // use the HttpClient mock created in this method for authentication, put the original one back afterwards
        $prevHttpClient = $this->client->getHttp();
        $this->client->setHttp($httpClientMock);

        $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');

        $this->client->setHttp($prevHttpClient);
    }

    public function testMethodReturnsModel()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-order'));
        $this->httpClientMock->method('request')->willReturn($response);

        $order = $this->client->getOrder('test');
        $this->assertInstanceOf(AbstractModel::class, $order);
    }

    public function testMethodUnwrapsMonoFieldResponse()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-reduced-orders'));
        $this->httpClientMock->method('request')->willReturn($response);

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

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $deliveryOptions = $this->client->getDeliveryOptions([]);

        $this->assertEquals([], $deliveryOptions);
    }

    public function testMethodWrapsScalarArgumentToMonoFieldRequest()
    {
        $body = null;
        $this->httpClientMock->method('request')->with('POST')
            ->willReturnCallback(function ($method, $uri, $options) use (&$body) {
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
        $this->httpClientMock->method('request')->with('POST')
            ->willReturnCallback(function ($method, $uri, $options) use (&$body) {
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
        $this->httpClientMock->method('request')->willReturn($response);

        $reducedOrders = $this->client->getOrders();
        $this->assertEquals([], $reducedOrders);
    }
}
