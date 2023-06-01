<?php

namespace Picqer\BolRetailerV10\Model\Authentication;

use Picqer\BolRetailerV10\Model\AbstractModel;

class TokenRequest extends AbstractModel
{
    public function getModelDefinition(): array
    {
        return [
            'grant_type' => [ 'model' => null, 'array' => false ],
            'code' => [ 'model' => null, 'array' => true ],
            'redirect_uri' => [ 'model' => null, 'array' => true ],
            'refresh_token' => [ 'model' => null, 'array' => true ],
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
