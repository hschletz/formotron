<?php

namespace Formotron\Test\Attributes;

use Formotron\Attribute\Validate;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Validator;
use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testWithoutArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('validate')->with('bar', []);
        $services = [[Validator::class, $validator]];

        $dataObject = new class
        {
            #[Validate(Validator::class)]
            public mixed $foo;
        };
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testPositionalArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('validate')->with('bar', ['value1', 'value2']);
        $services = [[Validator::class, $validator]];

        $dataObject = new class
        {
            #[Validate(Validator::class, 'value1', 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testNamedArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('validate')->with('bar', ['arg1' => 'value1', 'arg2' => 'value2']);
        $services = [[Validator::class, $validator]];

        $dataObject = new class
        {
            #[Validate(Validator::class, arg1: 'value1', arg2: 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }
}
