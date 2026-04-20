<?php

namespace Picqer\BolRetailerV10\Tests\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeneratedEnumsTest extends TestCase
{
    public static function provideGeneratedEnumClassnames(): array
    {
        $fileNames = scandir("src/Enum");

        $fileNames = array_filter($fileNames, function ($fileName) {
            return ! in_array($fileName, ['.', '..']);
        });

        return array_map(function ($fileName) {
            return ['Picqer\\BolRetailerV10\\Enum\\' . substr($fileName, 0, -4)];
        }, $fileNames);
    }

    #[DataProvider('provideGeneratedEnumClassnames')]
    public function testGeneratedEnumCanBeInstantiated(string $enumClassname)
    {
        $cases = $enumClassname::cases();
        $this->assertIsArray($cases);
        $this->assertNotNull($cases);
        $this->assertInstanceOf(\UnitEnum::class, $cases[0]);
    }
}
