<?php

namespace Jobjen\BolRetailerV10\Model\Authentication;

use Jobjen\BolRetailerV10\Model\AbstractModel;

class TokenRequest extends AbstractModel
{
    public function getModelDefinition(): array
    {
        return [
            'grant_type' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'code' => [ 'model' => null, 'enum' => null, 'array' => true ],
            'redirect_uri' => [ 'model' => null, 'enum' => null, 'array' => true ],
            'refresh_token' => [ 'model' => null, 'enum' => null, 'array' => true ],
        ];
    }

    /**
     * @var string
     */
    public $grant_type;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $redirect_uri;

    /**
     * @var string
     */
    public $refresh_token;
}
