<?php

namespace Picqer\BolRetailer\Exception;

use Exception;

class AuthenticationException extends \Exception
{
    public function __construct(?string $message = null, int $code = 0, Exception $previous = null)
    {
        $message = $message ?: 'An unknown error occurred during the authentication with Bol.com';

        parent::__construct($message, $code, $previous);
    }
}
