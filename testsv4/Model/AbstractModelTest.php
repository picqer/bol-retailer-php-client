<?php


namespace Picqer\BolRetailerV4\Tests\Model;

use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV4\Model\AbstractModel;

class AbstractModelTest extends TestCase
{
    public function testScalarIsSetFromArray()
    {
        $stub = new class () extends AbstractModel {
            public $foo;

            public function getModelDefinition(): array
            {
                return [
                    'foo' => [ 'model' => null, 'array' => false ]
                ];
            }
        };

        $stub->fromArray(['foo' => 'bar']);

        $this->assertEquals('bar', $stub->foo);
    }

    public function testUndefinedScalarIsIgnoredFromArray()
    {
        $stub = new class () extends AbstractModel {
            public function getModelDefinition(): array
            {
                return [];
            }
        };

        $stub->fromArray(['undefinedScalar' => 'bar']);

        $this->assertObjectNotHasAttribute('undefinedScalar', $stub);
    }

    public function testRelatedModelIsCreatedFromArray()
    {
        $relationStub = new class () extends AbstractModel {
            public $foo;

            public function getModelDefinition(): array
            {
                return [
                    'foo' => [ 'model' => null, 'array' => false ]
                ];
            }
        };

        $relationStubClass = get_class($relationStub);

        $stub = new class ($relationStubClass) extends AbstractModel {
            private $relationStubClass;

            public $relation;

            public function __construct($relationStubClass = null)
            {
                $this->relationStubClass = $relationStubClass;
            }

            public function getModelDefinition(): array
            {
                return [
                    'relation' => [ 'model' => $this->relationStubClass, 'array' => false ]
                ];
            }
        };

        $stub->fromArray([
            'relation' => [ 'foo' => 'bar' ]
        ]);

        $this->assertInstanceOf($relationStubClass, $stub->relation);
        $this->assertEquals('bar', $stub->relation->foo);
    }

    public function testUndefinedRelatedModelIsIgnoredFromArray()
    {
        $stub = new class () extends AbstractModel {
            public function getModelDefinition(): array
            {
                return [];
            }
        };

        $stub->fromArray([
            'undefinedRelation' => [ 'foo' => 'bar' ]
        ]);

        $this->assertObjectNotHasAttribute('undefinedRelation', $stub);
    }

    public function testRelatedModelArrayIsCreatedFromArray()
    {
        $relationStub = new class () extends AbstractModel {
            public $foo;

            public function getModelDefinition(): array
            {
                return [
                    'foo' => [ 'model' => null, 'array' => false ]
                ];
            }
        };

        $relationStubClass = get_class($relationStub);

        $stub = new class ($relationStubClass) extends AbstractModel {
            private $relationStubClass;

            public $relations;

            public function __construct($relationStubClass = null)
            {
                $this->relationStubClass = $relationStubClass;
            }

            public function getModelDefinition(): array
            {
                return [
                    'relations' => [ 'model' => $this->relationStubClass, 'array' => true ]
                ];
            }
        };

        $stub->fromArray([
            'relations' => [
                [ 'foo' => 'bar' ],
                [ 'foo' => 'bar2' ]
            ]
        ]);

        $this->assertIsArray($stub->relations);
        $this->assertEquals('bar', $stub->relations[0]->foo);
        $this->assertEquals('bar2', $stub->relations[1]->foo);
    }
}
