<?php
namespace Picqer\BolRetailer\Model;

abstract class AbstractModel
{
    /** @var array */
    protected $data = [];

    /**
     * Constructor.
     *
     * @param array $data The data of the model.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function __get(string $property)
    {
        $getter = sprintf('get%s', ucfirst($property));

        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        return $this->data[$property] ?? null;
    }

    /**
     * Merge the given data into the model.
     *
     * @param array $data The data to merge into the model.
     */
    public function merge(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }
}
