<?php

namespace Formotron\Test\Attributes;

use Formotron\Attribute\Key;
use Formotron\Attribute\MapKeys;
use Formotron\KeyMapper;
use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class MapKeysTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testMapKeysAttribute()
    {
        $keyMapper = new class implements KeyMapper {
            public function getKey(string $property): string
            {
                return strtoupper($property);
            }
        };
        $dataObject = new #[MapKeys('key_mapper')] class {
            public string $foo;
        };
        $result = $this->process(['FOO' => 'bar'], $dataObject, [['key_mapper', $keyMapper]]);

        $this->assertEquals('bar', $result->foo);
    }

    public function testMapKeysAttributePrecededByKeyAttribute()
    {
        $keyMapper = new class implements KeyMapper {
            public function getKey(string $property): string
            {
                TestCase::assertEquals('foo', $property);
                return strtoupper($property);
            }
        };
        $dataObject = new #[MapKeys('key_mapper')] class {
            public string $foo;

            #[Key('other')]
            public string $bar;
        };
        $result = $this->process(
            [
                'FOO' => 'bar',
                'other' => 'baz',
            ],
            $dataObject,
            [['key_mapper', $keyMapper]],
        );

        $this->assertEquals('bar', $result->foo);
        $this->assertEquals('baz', $result->bar);
    }

    public function testMapKeysAttributeWithInvalidService()
    {
        $keyMapper = new stdClass();
        $services = [['KeyMapper', $keyMapper]];

        $dataObject = new #[MapKeys('KeyMapper')] class {};
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service KeyMapper does not implement ' . KeyMapper::class);
        $this->process(['key1' => ''], $dataObject, $services);
    }
}
