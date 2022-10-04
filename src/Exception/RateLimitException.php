<?php


namespace Picqer\BolRetailerV8\Exception;

class RateLimitException extends RequestException
{
    /** @var null|int  */
    protected $retryAfter = null;

    public function setRetryAfter(?int $retryAfter): void
    {
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
