<?php

namespace Picqer\BolRetailerV10;

class AuthToken
{
    /**
     * @var string The raw token.
     */
    private $token;

    /**
     * @var int Unix timestamp when the token expires.
     */
    private $expiresAt;

    /**
     * @param string $token The raw token.
     * @param int $expiresAt Unix timestamp when the token expires.
     */
    public function __construct(string $token, int $expiresAt)
    {
        $this->token = $token;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Returns the raw token.
     * @return string The raw token.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Returns the expiration unix timestamp of the token.
     * @return int Unix timestamp when the token expires.
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Returns whether the token is expired.
     * @return bool Whether the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= time();
    }

    /**
     * Encodes the token to a string.
     * @return string The token encoded as a string.
     */
    public function __toString(): string
    {
        return json_encode([
            'token' => $this->token,
            'expiresAt' => $this->expiresAt,
        ]);
    }

    /**
     * Decodes a token from a string.
     * @param ?string $data Token encoded as string
     * @return ?self The token or null if the encoded token was invalid
     */
    public static function fromString(?string $data): ?self
    {
        if ($data === null) {
            return null;
        }

        $data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!isset($data['token']) || !isset($data['expiresAt'])) {
            return null;
        }

        return new self($data['token'], $data['expiresAt']);
    }
}
