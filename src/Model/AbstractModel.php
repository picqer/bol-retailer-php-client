<?php

namespace Picqer\BolRetailerV5\Model;

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
    public static function constructFromArray(array $data): AbstractModel
    {
        $model = new static;
        $model->fromArray($data);
        return $model;
    }

    /**
     * Fills the fields in this model from an array. Any related models are also created.
     * @param array $data Associative array with field values
     */
    public function fromArray(array $data): void
    {
        foreach ($this->getModelDefinition() as $field => $definition) {
            if (! isset($data[$field])) {
                continue;
            }

            if ($definition['model'] == null) {
                $this->$field = $data[$field];
            } elseif ($definition['array']) {
                $this->$field = array_map(function ($data) use ($definition) {
                    if ($data instanceof AbstractModel) {
                        return $data;
                    } else {
                        return $definition['model']::constructFromArray($data);
                    }
                }, $data[$field]);
            } else {
                if ($data[$field] instanceof AbstractModel) {
                    $this->$field = $data[$field];
                } else {
                    $this->$field = $definition['model']::constructFromArray($data[$field]);
                }
            }
        }
    }

    /**
     * Returns an associative array with values of this model and related models.
     * @param bool $omitNullValues Whether to omit fields that have the value null.
     * @return array Associative array with values of this model and related models
     */
    public function toArray($omitNullValues = true): array
    {
        $data = [];

        foreach ($this->getModelDefinition() as $field => $definition) {
            if ($omitNullValues && $this->$field === null) {
                continue;
            }

            if ($definition['model'] == null) {
                $data[$field] = $this->$field;
            } elseif ($definition['array']) {
                $data[$field] = array_map(function ($model) use ($omitNullValues) {
                    return $model->toArray($omitNullValues);
                }, array_values($this->$field));
            } else {
                $data[$field] = $this->$field->toArray($omitNullValues);
            }
        }

        return $data;
    }
}
