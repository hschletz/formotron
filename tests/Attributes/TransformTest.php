<?php

namespace Formotron\Test\Attributes;

use Formotron\Attribute\Transform;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Transformer;
use PHPUnit\Framework\TestCase;

class TransformTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testWithoutArguments()
    {
        $transformer = $this->createMock(Transformer::class);
        $transformer->method('transform')->with('a', [])->willReturn('b');
        $services = [[Transformer::class, $transformer]];

        $dataObject = new class
        {
            #[Transform(Transformer::class)]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'a'], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('b', $result->foo);
    }

    public function testExtraPositionalArguments()
    {
        $transformer = $this->createMock(Transformer::class);
        $transformer->expects($this->once())->method('transform')->with($this->anything(), ['value1', 'value2']);
        $services = [[Transformer::class, $transformer]];

        $dataObject = new class
        {
            #[Transform(Transformer::class, 'value1', 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }

    public function testExtraNamedArguments()
    {
        $transformer = $this->createMock(Transformer::class);
        $transformer->expects($this->once())->method('transform')->with($this->anything(), ['arg1' => 'value1', 'arg2' => 'value2']);
        $services = [[Transformer::class, $transformer]];

        $dataObject = new class
        {
            #[Transform(Transformer::class, arg1: 'value1', arg2: 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }
}
