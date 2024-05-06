<?php

namespace Jobjen\BolRetailerV10\Tests\Model;

use PHPUnit\Framework\TestCase;

class GeneratedEnumsTest extends TestCase
{
    public function provideGeneratedEnumClassnames()
    {
        $fileNames = scandir("src/Enum");

        $fileNames = array_filter($fileNames, function ($fileName) {
            return ! in_array($fileName, ['.', '..']);
        });

        return array_map(function ($fileName) {
            return ['Jobjen\\BolRetailerV10\\Enum\\' . substr($fileName, 0, -4)];
        }, $fileNames);
    }

    /**
     * @dataProvider provideGeneratedEnumClassnames
     */
    public function testGeneratedEnumCanBeInstantiated(string $enumClassname)
    {
        $cases = $enumClassname::cases();
        $this->assertIsArray($cases);
        $this->assertNotNull($cases);
        $this->assertInstanceOf(\UnitEnum::class, $cases[0]);
    }
}
