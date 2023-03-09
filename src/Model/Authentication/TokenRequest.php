<?php

namespace Picqer\BolRetailerV8\Model\Authentication;

use Picqer\BolRetailerV8\Model\AbstractModel;

class TokenRequest extends AbstractModel
{
    public function getModelDefinition(): array
    {
        return [
            'grant_type' => [ 'model' => null, 'array' => false ],
            'code' => [ 'model' => null, 'array' => true ],
            'redirect_uri' => [ 'model' => null, 'array' => true ],
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
}
