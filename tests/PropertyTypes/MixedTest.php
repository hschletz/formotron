<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class MixedTest extends TestCase
{
    use DataProcessorTestTrait;

    public static function mixedTypeProvider()
    {
        return [
            ['foo'],
            [true],
            [42],
            [1.234],
            [[]],
            [new stdClass()],
        ];
    }

    #[DataProvider('mixedTypeProvider')]
    public function testTypelessProperty(mixed $value)
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.property */
            public $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertEquals($value, $result->foo);
    }

    #[DataProvider('mixedTypeProvider')]
    public function testMixedProperty(mixed $value)
    {
        $dataObject = new class
        {
            public mixed $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertEquals($value, $result->foo);
    }
}
