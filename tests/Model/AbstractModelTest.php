<?php


namespace Picqer\BolRetailerV5\Tests\Model;

use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV5\Model\AbstractModel;

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

    public function testValidAbsentFieldIsUntouchedInModelFromArray()
    {
        $stub = new class () extends AbstractModel {
            public $foo = [];

            public function getModelDefinition(): array
            {
                return [
                    'foo' => [ 'model' => null, 'array' => true ]
                ];
            }
        };

        $stub->fromArray(['undefinedScalar' => 'some value']);

        $this->assertEquals([], $stub->foo);
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

    public function testNonNullScalarFieldIsPresentInArray()
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

        $stub->foo = 'bar';

        $this->assertEquals(['foo' => 'bar'], $stub->toArray());
    }

    public function testNullFieldIsOmittedFromArray()
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

        $this->assertEquals([], $stub->toArray());
    }

    public function testNullFieldIsPresentInArray()
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

        $this->assertEquals(['foo' => null], $stub->toArray(false));
    }

    public function testModelFieldIsPresentInArray()
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

        $relationStub->foo = 'bar';
        $stub->relation = $relationStub;

        $this->assertEquals(['relation' => ['foo' => 'bar']], $stub->toArray());
    }

    public function testModelArrayFieldIsPresentInArray()
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

        $rel1 = new $relationStubClass();
        $rel1->foo = 'bar1';

        $rel2= new $relationStubClass();
        $rel2->foo = 'bar2';

        $stub->relations = [$rel1, $rel2];

        $this->assertEquals(['relations' => [['foo' => 'bar1'], ['foo'=> 'bar2']]], $stub->toArray());
    }

    public function testArrayIndicesAreSanitized()
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

        $rel1 = new $relationStubClass();
        $rel1->foo = 'bar1';

        $rel2= new $relationStubClass();
        $rel2->foo = 'bar2';

        $stub->relations = [
            'a' => $rel1,
            '2' => $rel2
        ];

        $this->assertEquals(['relations' => [['foo' => 'bar1'], ['foo'=> 'bar2']]], $stub->toArray());
    }
}
