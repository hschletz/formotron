<?php

namespace Formotron\Test\Attributes;

use Attribute;
use Formotron\AssertionFailedException;
use Formotron\Attribute\Assert;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Validator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CustomValidatorAttribute extends Assert
{
    public function __construct(string $arg1, string $arg2)
    {
        parent::__construct('TestValidator', $arg1, $arg2);
    }
}

class AssertTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testAssertionsPass()
    {
        $validator1 = $this->createMock(Validator::class);
        $validator1->expects($this->once())->method('getValidationErrors')->with('bar')->willReturn([]);

        $validator2 = $this->createMock(Validator::class);
        $validator2->expects($this->once())->method('getValidationErrors')->with('bar')->willReturn([]);

        $services = [
            ['Validator1', $validator1],
            ['Validator2', $validator2],
        ];

        $dataObject = new class
        {
            #[Assert('Validator1')]
            #[Assert('Validator2')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'bar'], $dataObject, $services);
        $this->assertEquals('bar', $result->foo);
    }

    public function testAssertionFails()
    {
        $validator = $this->createMock(Validator::class);
        $validator->method('getValidationErrors')->willReturn(['error1', 'error2']);
        $services = [['Validator', $validator]];

        $dataObject = new class
        {
            #[Assert('Validator')]
            public mixed $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Assertion Validator failed on $foo');
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testExtraPositionalArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('getValidationErrors')->with($this->anything(), ['value1', 'value2']);
        $services = [['TestValidator', $validator]];

        $dataObject = new class
        {
            #[Assert('TestValidator', 'value1', 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }

    public function testExtraNamedArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('getValidationErrors')->with($this->anything(), ['arg1' => 'value1', 'arg2' => 'value2']);
        $services = [['TestValidator', $validator]];

        $dataObject = new class
        {
            #[Assert('TestValidator', arg1: 'value1', arg2: 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }

    public function testCustomAttribute()
    {
        $validator = new class implements Validator
        {
            public bool $hasRun = false;

            public function getValidationErrors(mixed $value, array $args): array
            {
                TestCase::assertEquals('a', $value);
                TestCase::assertEquals(['value1', 'value2'], $args);
                $this->hasRun = true;

                return [];
            }
        };
        $services = [['TestValidator', $validator]];

        $dataObject = new class
        {
            #[CustomValidatorAttribute('value1', 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
        $this->assertTrue($validator->hasRun, 'Validator has not been invoked');
    }

    public function testInvalidService()
    {
        $validator = new stdClass();
        $services = [['Validator', $validator]];

        $dataObject = new class
        {
            #[Assert('Validator')]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service Validator does not implement ' . Validator::class);
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }
}
