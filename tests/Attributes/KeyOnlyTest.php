<?php

namespace Formotron\Test\Attributes;

use Formotron\Attribute\Key;
use Formotron\Attribute\KeyOnly;
use Formotron\Attribute\Transform;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Transformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class KeyOnlyTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testKeyMissing()
    {
        $dataObject = new class
        {
            #[KeyOnly]
            public bool $key;
        };
        $result = $this->process([], $dataObject);
        $this->assertFalse($result->key);
    }

    public function testKeyPresent()
    {
        $dataObject = new class
        {
            #[KeyOnly]
            public bool $key;
        };
        $result = $this->process(['key' => 'value'], $dataObject);
        $this->assertTrue($result->key);
    }

    public function testDefaultValueIgnored()
    {
        $dataObject = new class
        {
            #[KeyOnly]
            public bool $key = true;
        };
        $result = $this->process([], $dataObject);
        $this->assertFalse($result->key);
    }

    public function testKeyRenamed()
    {
        $dataObject = new class
        {
            #[Key('key')]
            #[KeyOnly]
            public bool $foo;
        };
        $result = $this->process(['key' => 'value'], $dataObject);
        $this->assertTrue($result->foo);
    }

    public static function valueGetsTransformedProvider()
    {
        return [
            [[], 'unset'],
            [['key' => 'value'], 'set'],
        ];
    }

    // @phpstan-ignore missingType.iterableValue
    #[DataProvider('valueGetsTransformedProvider')]
    public function testValueGetsTransformed(array $input, string $expected)
    {
        $transformer = $this->createStub(Transformer::class);
        $transformer->method('transform')->willReturnCallback(fn(bool $value) => $value ? 'set' : 'unset');

        $dataObject = new class
        {
            #[KeyOnly]
            #[Transform('transform')]
            public string $key;
        };

        $result = $this->process($input, $dataObject, [['transform', $transformer]]);
        $this->assertEquals($expected, $result->key);
    }
}
