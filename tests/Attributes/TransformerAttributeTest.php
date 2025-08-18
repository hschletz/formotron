<?php

namespace Formotron\Test\Attributes;

use Attribute;
use Formotron\Attribute\Assert;
use Formotron\Attribute\Transform;
use Formotron\Attribute\TransformerAttribute;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Validator;
use LogicException;
use PHPUnit\Framework\TestCase;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SimpleAttributeWithoutArgs implements TransformerAttribute
{
    public function transform(mixed $value): mixed
    {
        assert(is_string($value));
        return 'prefix' . $value . 'suffix';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class SimpleAttributeWithArgs implements TransformerAttribute
{
    public function __construct(private string $prefix, private string $suffix) {}

    public function transform(mixed $value): mixed
    {
        return $this->prefix . '_' . $this->suffix;
    }
}

class TransformerAttributeTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testWithoutArguments()
    {
        $dataObject = new class
        {
            #[SimpleAttributeWithoutArgs]
            public mixed $foo;
        };
        $result = $this->process(['foo' => '_'], $dataObject);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('prefix_suffix', $result->foo);
    }

    public function testPositionalArguments()
    {
        $dataObject = new class
        {
            #[SimpleAttributeWithArgs('prefix', 'suffix')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => '_'], $dataObject);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('prefix_suffix', $result->foo);
    }

    public function testNamedArguments()
    {
        $dataObject = new class
        {
            #[SimpleAttributeWithArgs(suffix: 'suffix', prefix: 'prefix')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => '_'], $dataObject);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('prefix_suffix', $result->foo);
    }

    public function testMultipleTransformerAttributes()
    {
        $dataObject = new class
        {
            #[SimpleAttributeWithoutArgs]
            #[SimpleAttributeWithArgs('val1', 'val2')]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only 1 transformer can be attached to a property');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testMultipleMixedAttributes()
    {
        $dataObject = new class
        {
            #[SimpleAttributeWithoutArgs]
            #[Transform('transformer')]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only 1 transformer can be attached to a property');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testTransformedValueGetsValidated()
    {
        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('getValidationErrors')->with('prefix_suffix')->willReturn([]);

        $services = [
            ['TestValidator', $validator],
        ];

        $dataObject = new class
        {
            #[SimpleAttributeWithoutArgs]
            #[Assert('TestValidator')]
            public mixed $foo;
        };
        $this->process(['foo' => '_'], $dataObject, $services);
    }
}
