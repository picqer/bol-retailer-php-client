<?php


namespace Picqer\BolRetailerV10\Exception;

class UnauthorizedException extends RequestException
{
    public function accessTokenExpired(): bool
    {
        return stripos($this->getMessage(), 'JWT expired') !== false;
    }
}
