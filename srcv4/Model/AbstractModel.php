<?php

namespace Picqer\BolRetailerV4\Model;

abstract class AbstractModel
{
    /**
     * Returns the definition of the model: an associative array with field names as key and
     * field definition as value. The field definition contains of
     * model: Model class or null if it is a scalar type
     * array: Boolean whether it is an array
     * @return array The model definition
     */
    abstract public function getModelDefinition(): array;

    /**
     * Creates an instance of the Model from an associative array with data. Any related models are also created.
     * @param array $data Associative array with field values
     * @return AbstractModel
     */
    public static function fromArray(array $data): AbstractModel
    {
        $model = new static;

        // TODO validate that all fields are there
        foreach ($model->getModelDefinition() as $field => $definition) {
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
