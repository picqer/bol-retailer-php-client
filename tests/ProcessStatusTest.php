<?php
namespace Picqer\BolRetailer\Tests;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use GuzzleHttp\ClientInterface;
use Picqer\BolRetailer\Model;
use Picqer\BolRetailer\Client;
use Picqer\BolRetailer\ProcessStatus;
use Picqer\BolRetailer\Exception\ProcessStillPendingException;
use Picqer\BolRetailer\Exception\ProcessStatusNotFoundException;
use Psr\Http\Message\RequestInterface;

class ProcessStatusTest extends \PHPUnit\Framework\TestCase
{
    private $http;

    public function setup(): void
    {
        $this->http = $this->prophesize(ClientInterface::class);

        Client::setHttp($this->http->reveal());
    }

    public function testGetSingleProcessStatusById()
    {
        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-process-status-success'));

        $this->http
            ->request('GET', 'process-status/1', [])
            ->willReturn($response);

        $processStatus = ProcessStatus::get('1');

        $this->assertInstanceOf(Model\ProcessStatus::class, $processStatus);
        $this->assertEquals('1', $processStatus->id);
    }

    public function testRefreshFromServer()
    {
        $responses = [
            Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-process-status-pending')),
            Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-process-status-success')),
        ];

        $this->http
            ->request('GET', 'process-status/1', [])
            ->willReturn($responses[0], $responses[1]);

        $processStatus = ProcessStatus::get('1');
        $this->assertTrue($processStatus->isPending);

        $processStatus->refresh();
        $this->assertTrue($processStatus->isSuccess);
    }

    public function testWaitUntilComplete()
    {
        $responses = [
            Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-process-status-pending')),
            Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-process-status-success')),
        ];

        $this->http
            ->request('GET', 'process-status/1', [])
            ->willReturn($responses[0], $responses[1]);

        $processStatus = ProcessStatus::get('1');
        $this->assertTrue($processStatus->isPending);

        $processStatus->waitUntilComplete(5, 0);
        $this->assertTrue($processStatus->isSuccess);
    }

    public function testThrowExceptionIfRetryLimitIsReached()
    {
        $this->expectException(ProcessStillPendingException::class);
        $this->expectExceptionMessage('The process "1" is still in status "PENDING" after the maximum number of retries is reached.');

        $response = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/200-process-status-pending'));

        $this->http
            ->request('GET', 'process-status/1', [])
            ->shouldBeCalledTimes(21)
            ->willReturn($response);

        $processStatus = ProcessStatus::get('1');
        $processStatus->waitUntilComplete(20, 0);
    }

    public function testThrowExceptionWhenProcessStatusNotFound()
    {
        $this->expectException(ProcessStatusNotFoundException::class);

        $request   = $this->prophesize(RequestInterface::class);
        $response  = Psr7\parse_response(file_get_contents(__DIR__ . '/Fixtures/http/404-not-found'));
        $exception = new ClientException('', $request->reveal(), $response);

        $this->http
            ->request('GET', 'process-status/1234', [])
            ->willThrow($exception);

        ProcessStatus::get('1234');
    }
}
