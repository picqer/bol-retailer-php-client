<?php

namespace Picqer\BolRetailerV10\Model\Authentication;

use Picqer\BolRetailerV10\Model\AbstractModel;

class TokenResponse extends AbstractModel
{
    public function getModelDefinition(): array
    {
        return [
            'expires_in' => [ 'model' => null, 'array' => false ],
            'refresh_token' => [ 'model' => null, 'array' => true ],
            'token_type' => [ 'model' => null, 'array' => true ],
            'scope' => [ 'model' => null, 'array' => true ],
            'access_token' => [ 'model' => null, 'array' => true ],
        ];
    }

    /**
     * @var int|string
     */
    public $expires_in;

    /**
     * @var ?string
     */
    public $refresh_token;

    /**
     * @var string
     */
    public $token_type;

    /**
     * @var string
     */
    public $scope;

    /**
     * @var string
     */
    public $access_token;
}
