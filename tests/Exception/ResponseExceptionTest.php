<?php


namespace Picqer\BolRetailerV5\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV5\Exception\ResponseException;

class ResponseExceptionTest extends TestCase
{
    public function testUnknownDetailKeyIsNull()
    {
        $e = new ResponseException(
            "All your base are belong to us",
            499
        );

        $this->assertNull($e->getDetailKey());
    }

    public function testAccountInactiveIsDetectedAsDetailKey()
    {
        $e = new ResponseException(
            "Account is not active, access denied. Please contact partnerservice if this is unexpected.",
            403
        );

        $this->assertEquals(ResponseException::ACCOUNT_INACTIVE, $e->getDetailKey());
    }
}
