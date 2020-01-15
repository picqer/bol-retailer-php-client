<?php
namespace Picqer\BolRetailer\Tests\Exception;

use Picqer\BolRetailer\Exception\HttpException;

class HttpExceptionTest extends \PHPUnit\Framework\TestCase
{
    private $previous;
    private $exception;

    public function setup(): void
    {
        $this->previous  = new \Exception();
        $this->exception = new HttpException(
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/error.json'), true),
            404,
            $this->previous
        );
    }

    public function testContainsType()
    {
        $this->assertEquals('http://api.bol.com/problems', $this->exception->getType());
    }

    public function testContainsMessage()
    {
        $this->assertEquals('Error validating request body. Consult the bol.com API documentation for more information.', $this->exception->getMessage());
    }

    public function testContainsStatus()
    {
        $this->assertEquals('40X', $this->exception->getStatus());
    }

    public function testContainsDetail()
    {
        $this->assertEquals('Bad request', $this->exception->getDetail());
    }

    public function testContainsError()
    {
        $this->assertEquals(
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/error.json'), true),
            $this->exception->getError()
        );
    }
}
