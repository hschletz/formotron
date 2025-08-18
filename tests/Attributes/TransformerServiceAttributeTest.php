<?php

namespace Formotron\Test\Attributes;

use Attribute;
use Formotron\Attribute\Assert;
use Formotron\Attribute\TransformerServiceAttribute;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Transformer;
use Formotron\Validator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ServiceAttributeWithoutArgs implements TransformerServiceAttribute
{
    public function __construct() {}

    public function getServiceName(): string
    {
        return 'TransformerService1';
    }

    public function getArguments(): array
    {
        return [];
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class ServiceAttributeWithArgs implements TransformerServiceAttribute
{
    public function __construct(private string $arg1, private string $arg2) {}

    public function getServiceName(): string
    {
        return 'TransformerService2';
    }

    public function getArguments(): array
    {
        return get_object_vars($this);
    }
}

class TransformerServiceAttributeTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testWithoutArguments()
    {
        $transformer = new class implements Transformer
        {
            public function transform(mixed $value, array $args): mixed
            {
                TestCase::assertEquals('a', $value);
                TestCase::assertEquals([], $args);
                return 'b';
            }
        };
        $services = [['TransformerService1', $transformer]];

        $dataObject = new class
        {
            #[ServiceAttributeWithoutArgs]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'a'], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('b', $result->foo);
    }

    public function testPositionalArguments()
    {
        $transformer = new class implements Transformer
        {
            public function transform(mixed $value, array $args): mixed
            {
                TestCase::assertEquals('a', $value);
                TestCase::assertEquals(['arg1' => 'val1', 'arg2' => 'val2'], $args);
                return 'b';
            }
        };
        $services = [['TransformerService2', $transformer]];

        $dataObject = new class
        {
            #[ServiceAttributeWithArgs('val1', 'val2')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'a'], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('b', $result->foo);
    }

    public function testNamedArguments()
    {
        $transformer = new class implements Transformer
        {
            public function transform(mixed $value, array $args): mixed
            {
                TestCase::assertEquals('a', $value);
                TestCase::assertEquals(['arg1' => 'val1', 'arg2' => 'val2'], $args);
                return 'b';
            }
        };
        $services = [['TransformerService2', $transformer]];

        $dataObject = new class
        {
            #[ServiceAttributeWithArgs(arg2: 'val2', arg1: 'val1')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'a'], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('b', $result->foo);
    }

    public function testMultipleAttributes()
    {
        $dataObject = new class
        {
            #[ServiceAttributeWithoutArgs]
            #[ServiceAttributeWithArgs('val1', 'val2')]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only 1 transformer can be attached to a property');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testAttributeWithInvalidService()
    {
        $transformer = new stdClass();
        $services = [['TransformerService1', $transformer]];

        $dataObject = new class
        {
            #[ServiceAttributeWithoutArgs]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service TransformerService1 does not implement ' . Transformer::class);
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testTransformedValueGetsValidated()
    {
        $transformer = new class implements Transformer
        {
            public function transform(mixed $value, array $args): mixed
            {
                return 'b';
            }
        };

        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('getValidationErrors')->with('b')->willReturn([]);

        $services = [
            ['TransformerService1', $transformer],
            ['TestValidator', $validator],
        ];

        $dataObject = new class
        {
            #[ServiceAttributeWithoutArgs]
            #[Assert('TestValidator')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }
}
