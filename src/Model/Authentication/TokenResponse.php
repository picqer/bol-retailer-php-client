<?php

namespace Jobjen\BolRetailerV10\Model\Authentication;

use Jobjen\BolRetailerV10\Model\AbstractModel;

class TokenResponse extends AbstractModel
{
    public function getModelDefinition(): array
    {
        return [
            'expires_in' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'refresh_token' => [ 'model' => null, 'enum' => null, 'array' => true ],
            'token_type' => [ 'model' => null, 'enum' => null, 'array' => true ],
            'scope' => [ 'model' => null, 'enum' => null, 'array' => true ],
            'access_token' => [ 'model' => null, 'enum' => null, 'array' => true ],
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
