<?php


namespace Picqer\BolRetailerV8\Exception;

class RateLimitException extends RequestException
{
    /** @var null|int  */
    protected $retryAfter = null;

    public function __construct(string $message, int $code, ?int $retryAfter)
    {
        parent::__construct($message, $code);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
