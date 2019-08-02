<?php
namespace Picqer\BolRetailer\Exception;

use Picqer\BolRetailer\Model\ProcessStatus;

class ProcessStillPendingException extends \RuntimeException
{
    /** @var ProcessStatus */
    private $processStatus;

    /**
     * Constructor.
     *
     * @param ProcessStatus $processStatus The process the exception was thrown for.
     */
    public function __construct(ProcessStatus $processStatus)
    {
        parent::__construct(sprintf(
            'The process "%s" is still in status "PENDING" after the maximum number of retries is reached.',
            $processStatus->id
        ));

        $this->processStatus = $processStatus;
    }
}
