<?php
namespace Picqer\BolRetailer\Tests;

use GuzzleHttp\Psr7;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Picqer\BolRetailer\Client;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    private $http;
    private $client;

    public function setup(): void
    {
        $this->http = $this->prophesize(ClientInterface::class);

        Client::setHttp($this->http->reveal());
    }

    public function testAuthenticateWithCredentials()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-token'));

        $this->http
            ->request('POST', 'https://login.bol.com/token', [
                'headers' => [ 'Accept' => 'application/json' ],
                'form_params' => [
                    'client_id' => 'secret_id',
                    'client_secret' => 'somesupersecretvaluethatshouldnotbeshared',
                    'grant_type' => 'client_credentials'
                ]
            ])->willReturn($response);

        Client::setCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
        $this->assertTrue(Client::isAuthenticated());

        Client::clearCredentials();
        $this->assertFalse(Client::isAuthenticated());
    }

    public function testPerformHttpRequest()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->http
            ->request('GET', 'status', [])
            ->willReturn($response);

        $this->assertEquals($response, Client::request('GET', 'status'));
    }

    public function testPerformHttpRequestWithOptions()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->http
            ->request('GET', 'status', [ 'query' => [ 'foo' => 'bar' ]])
            ->willReturn($response);

        $this->assertEquals($response, Client::request('GET', 'status', [ 'query' => [ 'foo' => 'bar' ]]));
    }

    public function testPerformHttpRequestWithUserAgent()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->http
            ->request('GET', 'status', [ 'headers' => [ 'User-Agent' => 'foo' ]])
            ->willReturn($response);

        Client::setUserAgent('foo');

        $this->assertEquals($response, Client::request('GET', 'status'));
    }
}
