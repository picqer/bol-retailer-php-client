<?php


namespace Picqer\BolRetailerV5\Exception;

class ResponseException extends Exception
{
    const ACCOUNT_INACTIVE = 'account_inactive';

    /**
     * @var string[] Mapping from detail message primary words to detail key
     */
    protected static $detailMap = [
        // Account is not active, access denied. Please contact partnerservice if this is unexpected.
        'Account is not active' => self::ACCOUNT_INACTIVE
    ];

    /**
     * Returns the detail key beloning to the the message. The detail key allows an easy way to distinguish between
     * different Response exceptions.
     * @return string|null Detail key belonging to the message or null if unknown.
     */
    public function getDetailKey(): ?string
    {
        foreach (self::$detailMap as $needle => $detail) {
            if (stristr($this->getMessage(), $needle)) {
                return $detail;
            }
        }

        return null;
    }
}
