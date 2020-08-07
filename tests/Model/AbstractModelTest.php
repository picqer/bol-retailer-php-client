<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\AbstractModel;

class AbstractModelTest extends \PHPUnit\Framework\TestCase
{
    private $model;

    public function setup(): void
    {
        $this->model = new FixtureModel([
            'foo' => 'lorem',
            'bar' => 'ipsum',
            'baz' => 'dolor',
            'qux' => null,
            'qax' => '',
        ]);
    }

    public function testChecksIfPropertyIsSet()
    {
        $this->assertTrue(isset($this->model->foo));
        $this->assertTrue(isset($this->model->bar));
        $this->assertTrue(isset($this->model->baz));
        $this->assertFalse(isset($this->model->qux));
        $this->assertTrue(isset($this->model->qax));
    }

    public function testChecksIfPropertyIsEmpty()
    {
        $this->assertFalse(empty($this->model->foo));
        $this->assertFalse(empty($this->model->bar));
        $this->assertFalse(empty($this->model->baz));
        $this->assertTrue(empty($this->model->qux));
        $this->assertTrue(empty($this->model->qax));
    }
}

class FixtureModel extends AbstractModel
{
    public function getFoo(): ?string
    {
        return isset($this->data['foo']) ? strtoupper($this->data['foo']) : null;
    }
}
