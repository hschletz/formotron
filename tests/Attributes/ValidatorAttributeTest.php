<?php

namespace Formotron\Test\Attributes;

use Attribute;
use Exception;
use Formotron\AssertionFailedException;
use Formotron\Attribute\Validate;
use Formotron\Attribute\ValidatorAttribute;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Validator;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

class ValidatorAttributeTestException extends Exception {}

#[Attribute(Attribute::TARGET_PROPERTY)]
class SimpleValidatorAttributeWithoutArgs implements ValidatorAttribute
{
    public function validate(mixed $value): void
    {
        ValidatorAttributeTest::$validatedValues[$value][] = [];
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class SimpleValidatorAttributeWithArgs implements ValidatorAttribute
{
    public function __construct(private string $arg1, private string $arg2) {}

    public function validate(mixed $value): void
    {
        ValidatorAttributeTest::$validatedValues[$value][] = [$this->arg1, $this->arg2];
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class SimpleFailingValidatorAttribute implements ValidatorAttribute
{
    public function validate(mixed $value): void
    {
        throw new Exception('Test validation failure');
    }
}

class ValidatorAttributeTest extends TestCase
{
    use DataProcessorTestTrait;

    /** @var array<string, mixed[]> */
    public static array $validatedValues;

    #[Before]
    public function resetValidatedValues()
    {
        static::$validatedValues = [];
    }

    public function testWithoutArguments()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithoutArgs]
            public mixed $foo;
        };
        $this->process(['foo' => 'bar'], $dataObject);
        $this->assertEquals(
            [
                'bar' => [
                    [],
                ],
            ],
            static::$validatedValues
        );
    }

    public function testPositionalArguments()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithArgs('a', 'b')]
            public mixed $foo;
        };
        $this->process(['foo' => 'bar'], $dataObject);
        $this->assertEquals(
            [
                'bar' => [
                    ['a', 'b'],
                ],
            ],
            static::$validatedValues,
        );
    }

    public function testNamedArguments()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithArgs(arg2: 'b', arg1: 'a')]
            public mixed $foo;
        };
        $this->process(['foo' => 'bar'], $dataObject);
        $this->assertEquals(
            [
                'bar' => [
                    ['a', 'b'],
                ],
            ],
            static::$validatedValues,
        );
    }

    public function testValidationFailure()
    {
        $dataObject = new class
        {
            #[SimpleFailingValidatorAttribute]
            public mixed $foo;
        };

        $this->expectExceptionMessage('Test validation failure');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testMultipleValidatorAttributes()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithoutArgs]
            #[SimpleValidatorAttributeWithArgs('a', 'b')]
            public mixed $foo;
        };
        $this->process(['foo' => 'bar'], $dataObject);
        $this->assertEquals(
            [
                'bar' => [
                    [],
                    ['a', 'b'],
                ]
            ],
            static::$validatedValues,
        );
    }

    public function testMultipleMixedAttributes()
    {
        $validator = $this->createStub(Validator::class);
        $validator->method('validate')->willReturnCallback(function ($value) {
            static::$validatedValues[$value][] = 'testValidator';
        });

        $services = [
            ['testValidator', $validator],
        ];

        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithoutArgs]
            #[Validate('testValidator')]
            public mixed $foo;
        };

        $this->process(['foo' => 'bar'], $dataObject, $services);
        $this->assertEquals(
            [
                'bar' => [
                    [],
                    'testValidator',
                ],
            ],
            static::$validatedValues,
        );
    }

    public function testNullBypassesValidationForNullableProperty()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithoutArgs]
            public ?string $foo;
        };

        $this->process(['foo' => null], $dataObject);
        $this->assertEmpty(static::$validatedValues);
    }

    public function testNullBypassesValidationForNonNullableProperty()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithoutArgs]
            public string $foo;
        };

        // process() will throw an exception. Ignore it, to be able to inspect
        // validation results.
        try {
            $this->process(['foo' => null], $dataObject);
            $this->fail('Expected exception was not thrown');
        } catch (AssertionFailedException) {
        }
        $this->assertEmpty(static::$validatedValues);
    }

    public function testNullGetsValidatedForMixedProperty()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithoutArgs]
            public mixed $foo;
        };

        $this->process(['foo' => null], $dataObject);
        $this->assertEquals(
            [
                '' => [
                    [],
                ],
            ],
            static::$validatedValues
        );
    }

    public function testNullGetsValidatedForUntypedProperty()
    {
        $dataObject = new class
        {
            #[SimpleValidatorAttributeWithoutArgs]
            public $foo; // @phpstan-ignore missingType.property (type is intentionally omitted)
        };

        $this->process(['foo' => null], $dataObject);
        $this->assertEquals(
            [
                '' => [
                    [],
                ],
            ],
            static::$validatedValues
        );
    }
}
