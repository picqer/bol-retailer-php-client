<?php
namespace Picqer\BolRetailer\Exception;

use Exception;

class HttpException extends \RuntimeException
{
    /** @var array */
    private $error;

    /**
     * Constructor.
     *
     * @param array     $error    The error message from Bol.co
     * @param integer   $code     The error code from Bol.com
     * @param Exception $previous The previous exception from Bol.com
     */
    public function __construct(array $error, int $code = 0, Exception $previous = null)
    {
        parent::__construct($error['title'], $code, $previous);

        $this->error = $error;
    }

    public function getType()
    {
        return $this->error['type'];
    }

    public function getStatus()
    {
        return $this->error['status'];
    }

    public function getDetail()
    {
        return $this->error['detail'];
    }

    public function getError()
    {
        return $this->error;
    }
}
