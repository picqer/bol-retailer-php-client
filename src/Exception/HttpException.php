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
     * @param array     $error    The error message from Bol.com
     * @param integer   $code     The error code from Bol.com
     * @param Exception $previous The previous exception from Bol.com
     */
    public function __construct(array $error, int $code = 0, Exception $previous = null)
    {
        parent::__construct($error['title'], $code, $previous);

        $this->error = $error;
    }

    /**
     * Get the type of the HTTP error.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->error['type'];
    }

    /**
     * Get the status code of the HTTP error.
     *
     * @return mixed
     */
    public function getStatus()
    {
        return $this->error['status'];
    }

    /**
     * Get the detail of the HTTP error.
     *
     * @return mixed
     */
    public function getDetail()
    {
        return $this->error['detail'];
    }

    /**
     * Get the actual HTTP error.
     *
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }
}
