<?php

namespace Picqer\BolRetailerV9\Tests\Model;

use PHPUnit\Framework\TestCase;

class GeneratedModelsTest extends TestCase
{
    public function provideGeneratedModelClassnames()
    {
        $fileNames = scandir("src/Model");

        $fileNames = array_filter($fileNames, function ($fileName) {
            return ! in_array($fileName, ['.', '..', 'AbstractModel.php', 'Authentication']);
        });

        return array_map(function ($fileName) {
            return ['Picqer\\BolRetailerV9\\Model\\' . substr($fileName, 0, -4)];
        }, $fileNames);
    }

    /**
     * @dataProvider provideGeneratedModelClassnames
     */
    public function testGeneratedModelCanBeInstantiated(string $modelClassname)
    {
        $instance = new $modelClassname();
        $this->assertEquals(get_class($instance), $modelClassname);
    }
}
