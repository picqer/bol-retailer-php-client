<?php

namespace Picqer\BolRetailerV8;

class JWTToken
{
    /**
     * @var string
     */
    private $encoded;

    /**
     * @param string $encoded The encoded token.
     */
    public function __construct(string $encoded)
    {
        $this->encoded = $encoded;
    }

    /**
     * Returns the encoded token.
     * @return string The encoded token.
     */
    public function getEncoded(): string
    {
        return $this->encoded;
    }

    /**
     * Returns whether the token is expired.
     * @return bool Whether the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->getExpirationTime() <= time();
    }

    /**
     * Returns the expiration time of the token.
     * @return int The expiration time of the token.
     */
    public function getExpirationTime(): int
    {
        return $this->getClaim('exp');
    }

    /**
     * Returns the claims set.
     * @return array The claims set.
     */
    public function getClaimsSet(): array
    {
        return json_decode(base64_decode(explode('.', $this->encoded)[1]), true);
    }

    /**
     * Returns the value of a claim.
     * @param string $claim
     * @return mixed|null The value of the claim, or null if the claim does not exist.
     */
    public function getClaim(string $claim)
    {
        $claims = $this->getClaimsSet();
        return $claims[$claim] ?? null;
    }
}
