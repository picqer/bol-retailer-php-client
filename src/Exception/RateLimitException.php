<?php


namespace Picqer\BolRetailerV10\Exception;

class RateLimitException extends RequestException
{
    /** @var int|null  */
    protected $retryAfter = null;

    public function __construct($message = '', $code = 0, \Exception $previous = null, $retryAfter = null)
    {
        parent::__construct($message, $code, $previous);

        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
