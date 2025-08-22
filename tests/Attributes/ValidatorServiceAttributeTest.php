<?php

namespace Formotron\Test\Attributes;

use Attribute;
use Exception;
use Formotron\Attribute\ValidatorServiceAttribute;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Validator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ValidatorServiceAttributeWithoutArgs implements ValidatorServiceAttribute
{
    public function __construct() {}

    public function getServiceName(): string
    {
        return 'ValidatorService1';
    }

    public function getArguments(): array
    {
        return [];
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class ValidatorServiceAttributeWithArgs implements ValidatorServiceAttribute
{
    public function __construct(private string $arg1, private string $arg2) {}

    public function getServiceName(): string
    {
        return 'ValidatorService2';
    }

    public function getArguments(): array
    {
        return get_object_vars($this);
    }
}

class ValidatorServiceAttributeTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testWithoutArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('validate')->with('bar', []);

        $services = [['ValidatorService1', $validator]];

        $dataObject = new class
        {
            #[ValidatorServiceAttributeWithoutArgs]
            public mixed $foo;
        };

        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testPositionalArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('validate')->with('bar', ['arg1' => 'val1', 'arg2' => 'val2']);

        $services = [['ValidatorService2', $validator]];

        $dataObject = new class
        {
            #[ValidatorServiceAttributeWithArgs('val1', 'val2')]
            public mixed $foo;
        };

        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testNamedArguments()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('validate')->with('bar', ['arg1' => 'val1', 'arg2' => 'val2']);

        $services = [['ValidatorService2', $validator]];

        $dataObject = new class
        {
            #[ValidatorServiceAttributeWithArgs(arg2: 'val2', arg1: 'val1')]
            public mixed $foo;
        };

        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testValidationFailure()
    {
        $validator = new class implements Validator {
            public function validate(mixed $value, array $args): void
            {
                throw new Exception('Test validation failure');
            }
        };
        $services = [['ValidatorService1', $validator]];

        $dataObject = new class
        {
            #[ValidatorServiceAttributeWithoutArgs]
            public mixed $foo;
        };

        $this->expectExceptionMessage('Test validation failure');
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testAttributeWithInvalidService()
    {
        $validator = new stdClass();
        $services = [['ValidatorService1', $validator]];

        $dataObject = new class
        {
            #[ValidatorServiceAttributeWithoutArgs]
            public mixed $foo;
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service ValidatorService1 does not implement ' . Validator::class);

        $this->process(['foo' => 'bar'], $dataObject, $services);
    }
}
