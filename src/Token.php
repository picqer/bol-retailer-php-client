<?php

namespace Picqer\BolRetailerV8;

use Picqer\BolRetailerV8\Model\Authentication\TokenResponse;

class Token
{
    /**
     * @var string
     */
    public $accessToken;

    /**
     * @var ?string
     */
    public $refreshToken;

    /**
     * @var string
     */
    public $scope;

    /**
     * @var int
     */
    public $expiresAt;

    /**
     * Creates a Token from a TokenResponse.
     * @param TokenResponse $tokenResponse
     * @return Token
     */
    public static function fromTokenResponse(TokenResponse $tokenResponse): Token
    {
        $token = new self();
        $token->accessToken = $tokenResponse->access_token;
        $token->refreshToken = $tokenResponse->refresh_token;
        $token->scope = $tokenResponse->scope;
        $token->expiresAt = time() + $tokenResponse->expires_in;

        return $token;
    }

    /**
     * Returns whether the token is expired.
     * @return bool Whether the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= time();
    }
}
