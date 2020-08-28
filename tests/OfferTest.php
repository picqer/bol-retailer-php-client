<?php
namespace Picqer\BolRetailer\Tests;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use GuzzleHttp\ClientInterface;
use Picqer\BolRetailer\Model;
use Picqer\BolRetailer\Offer;
use Picqer\BolRetailer\Client;
use Picqer\BolRetailer\ProcessStatus;
use Picqer\BolRetailer\Exception\OfferNotFoundException;
use Psr\Http\Message\RequestInterface;

class OfferTest extends \PHPUnit\Framework\TestCase
{
    private $http;

    public function setup(): void
    {
        $this->http = $this->prophesize(ClientInterface::class);

        Client::setHttp($this->http->reveal());
    }

    public function testGetOffer()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-offer'));

        $this->http
            ->request('GET', 'offers/6ff736b5-cdd0-4150-8c67-78269ee986f5', [])
            ->willReturn($response);

        $offer = Offer::get('6ff736b5-cdd0-4150-8c67-78269ee986f5');

        $this->assertInstanceOf(Model\Offer::class, $offer);
        $this->assertEquals('6ff736b5-cdd0-4150-8c67-78269ee986f5', $offer->offerId);
    }

    public function testThrowExceptionWhenProcessStatusNotFound()
    {
        $this->expectException(OfferNotFoundException::class);

        $request   = $this->prophesize(RequestInterface::class);
        $response  = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/404-not-found'));
        $exception = new ClientException('', $request->reveal(), $response);

        $this->http
            ->request('GET', 'offers/1234', [])
            ->willThrow($exception);

        Offer::get('1234');
    }

    public function testUpdateStockLevel()
    {
        $responses   = [];
        $responses[] = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-offer'));
        $responses[] = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/202-process-status'));

        $this->http
            ->request('GET', 'offers/6ff736b5-cdd0-4150-8c67-78269ee986f5', [])
            ->willReturn($responses[0]);

        $this->http
            ->request('PUT', 'offers/6ff736b5-cdd0-4150-8c67-78269ee986f5/stock', [ 'body' => json_encode([ 'amount' => 25, 'managedByRetailer' => true ]) ])
            ->willReturn($responses[1]);

        $offer = Offer::get('6ff736b5-cdd0-4150-8c67-78269ee986f5');
        $processStatus = $offer->updateStock(25, true);

        $this->assertInstanceOf(ProcessStatus::class, $processStatus);
    }

    public function testUpdatePricing()
    {
        $responses   = [];
        $responses[] = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-offer'));
        $responses[] = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/202-process-status'));

        $this->http
            ->request('GET', 'offers/6ff736b5-cdd0-4150-8c67-78269ee986f5', [])
            ->willReturn($responses[0]);

        $this->http
            ->request('PUT', 'offers/6ff736b5-cdd0-4150-8c67-78269ee986f5/price', [ 'body' => json_encode(['pricing' => ['bundlePrices'=> ['quantity' => 1, 'price' => 9.99]]])])
            ->willReturn($responses[1]);

        $offer = Offer::get('6ff736b5-cdd0-4150-8c67-78269ee986f5');
        $processStatus = $offer->updatePricing(['quantity' => 1, 'price' => 9.99]);

        $this->assertInstanceOf(ProcessStatus::class, $processStatus);
    }
}
