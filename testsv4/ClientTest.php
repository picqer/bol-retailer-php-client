<?php


namespace Picqer\BolRetailerV4\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV4\Client;
use Picqer\BolRetailerV4\Exception\ResponseException;
use Picqer\BolRetailerV4\Exception\UnauthorizedException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ClientTest extends TestCase
{
    use ProphecyTrait;

    /** @var Client */
    private $client;

    /** @var ObjectProphecy */
    private $http;

    public function setup(): void
    {
        $this->http = $this->prophesize(ClientInterface::class);
        $this->client = new Client();
        $this->client->setHttp($this->http->reveal());
    }

    public function testClientIsInitiallyNotAuthenticated()
    {
        $this->assertFalse($this->client->isAuthenticated());
    }

    public function testClientIsAuthenticatedAfterSuccessfulAuthentication()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token'));

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->http->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testClientThrowsUnauthorizedExceptionWhenAuthenticatingWithBadCredentials()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-unauthorized'));
        $clientException = new GuzzleClientException(
            'Client error',
            new Request('POST', 'dummy'),
            $response
        );

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->http->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willThrow($clientException);

        $this->expectException(UnauthorizedException::class);
        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function providerMalformedTokenResponses()
    {
        $files = [
            '200-token-missing-access_token',
            '200-token-empty-access_token',
            '200-token-missing-expires_in',
            '200-token-empty-expires_in',
            '200-token-unexpected-bearer',
            '200-token-unexpected-scope',
            '200-token-empty-body'
        ];

        return array_map(function ($file) {
            return [
                Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/' . $file))
            ];
        }, $files);
    }

    /**
     * @dataProvider providerMalformedTokenResponses
     */
    public function testClientThrowsResponseExceptionWhenTokenIsMalformed($response)
    {
        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->http->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        $this->expectException(ResponseException::class);
        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }
}
