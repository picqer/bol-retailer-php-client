<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\ProcessStatus;

class ProcessStatusTest extends \PHPUnit\Framework\TestCase
{
    private $processStatus;

    public function setup(): void
    {
        $this->processStatus = new ProcessStatus(
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/process-status.json'), true)
        );
    }

    public function testContainsId()
    {
        $this->assertEquals('1', $this->processStatus->id);
    }

    public function testContainsEntityId()
    {
        $this->assertEquals('555551', $this->processStatus->entityId);
    }

    public function testContainsEventType()
    {
        $this->assertEquals('CONFIRM_SHIPMENT', $this->processStatus->eventType);
    }

    public function testContainsDescription()
    {
        $this->assertEquals('Lorem ipsum dolor sit amet.', $this->processStatus->description);
    }

    public function testContainsStatus()
    {
        $this->assertEquals('PENDING', $this->processStatus->status);
    }

    /**
     * @dataProvider statusProvider
     */
    public function testContainsStatusProperties(
        string $status,
        bool $isPending,
        bool $isSuccess,
        bool $isFailure,
        bool $isTimeout
    ) {
        $this->processStatus->merge([ 'status' => $status ]);

        $this->assertEquals($isPending, $this->processStatus->isPending);
        $this->assertEquals($isSuccess, $this->processStatus->isSuccess);
        $this->assertEquals($isFailure, $this->processStatus->isFailure);
        $this->assertEquals($isTimeout, $this->processStatus->isTimeout);
    }

    public function statusProvider()
    {
        return [
            [ 'PENDING', true, false, false, false ],
            [ 'SUCCESS', false, true, false, false ],
            [ 'FAILURE', false, false, true, false ],
            [ 'TIMEOUT', false, false, false, true ],
        ];
    }
}
