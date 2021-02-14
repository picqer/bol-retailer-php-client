<?php

namespace Picqer\BolRetailerV4\Model;

abstract class AbstractModel
{
    protected static $modelDefinition = [];

    /**
     * Creates an instance of the Model from an associative array with data. Any related models are also created.
     * @param array $data Associative array with field values
     * @return AbstractModel
     */
    public static function fromArray(array $data): AbstractModel
    {
        $model = new static;

        // TODO validate that all fields are there
        foreach (static::$modelDefinition as $field => $definition) {
            if (! isset($data[$field])) {
                continue;
            }

            if ($definition['model'] == null) {
                $model->$field = $data[$field];
            } elseif ($definition['array']) {
                $model->$field = array_map(function ($data) use ($definition) {
                    if ($data instanceof AbstractModel) {
                        return $data;
                    } else {
                        return $definition['model']::fromArray($data);
                    }
                }, $data[$field]);
            } else {
                if ($data[$field] instanceof AbstractModel) {
                    $model->$field = $data[$field];
                } else {
                    $model->$field = $definition['model']::fromArray($data[$field]);
                }
            }
        }

        return $model;
    }
}
