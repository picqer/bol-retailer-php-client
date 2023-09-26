<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class AudioTracks extends AbstractModel
{
    /**
     * Returns the definition of the model: an associative array with field names as key and
     * field definition as value. The field definition contains of
     * model: Model class or null if it is a scalar type
     * array: Boolean whether it is an array
     * @return array The model definition
     */
    public function getModelDefinition(): array
    {
        return [
            'discNumber' => [ 'model' => null, 'array' => false ],
            'trackNumber' => [ 'model' => null, 'array' => false ],
            'discSide' => [ 'model' => null, 'array' => false ],
            'title' => [ 'model' => null, 'array' => false ],
            'artistName' => [ 'model' => null, 'array' => false ],
            'playTime' => [ 'model' => null, 'array' => false ],
            'clipUrl' => [ 'model' => null, 'array' => false ],
            'clipType' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var string The disc number within an album the audio track is stored on.
     */
    public $discNumber;

    /**
     * @var string The track number on the album.
     */
    public $trackNumber;

    /**
     * @var string The disc side on which the audio track is stored on.
     */
    public $discSide;

    /**
     * @var string The title of the audio track.
     */
    public $title;

    /**
     * @var string The name of the artist(s) performing the audio track.
     */
    public $artistName;

    /**
     * @var string The play time of the audio track.
     */
    public $playTime;

    /**
     * @var string The URL on which an audio clip of the audio track has been made available.
     */
    public $clipUrl;

    /**
     * @var string The format in which the audio clip is available.
     */
    public $clipType;
}
