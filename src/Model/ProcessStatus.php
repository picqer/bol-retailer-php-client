<?php
namespace Picqer\BolRetailer\Model;

/**
 * @property string $id           The process status id.
 * @property string $entityId     The id of the object being processed. E.g. in case of a shipment process id, you will
 *                                receive the id of the order item being processed.
 * @property string $eventType    Name of the requested action that is being processed.
 * @property string $description  Describes the action that is being processed.
 * @property string $status       Status of the action being processed.
 * @property string $errorMessage Shows error message if applicable.
 *
 * @property bool   $isPending    Indicates if the process is pending.
 * @property bool   $isSuccess    Indicates if the process is succesful.
 * @property bool   $isFailure    Indicates if the process is failure.
 * @property bool   $isTimeout    Indicates if the process is timed out.
 */
class ProcessStatus extends AbstractModel
{
    protected function getIsPending(): bool
    {
        return $this->status === 'PENDING';
    }

    protected function getIsSuccess(): bool
    {
        return $this->status === 'SUCCESS';
    }

    protected function getIsFailure(): bool
    {
        return $this->status === 'FAILURE';
    }

    protected function getIsTimeout(): bool
    {
        return $this->status === 'TIMEOUT';
    }
}
