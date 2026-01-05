<?php

namespace Formotron\Test;

use Attribute;
use Formotron\Attribute\Validate;
use Formotron\Attribute\ValidatorAttribute;
use Formotron\ValidationError;
use Formotron\ValidationFailedException;
use Formotron\Validator;
use PHPUnit\Framework\TestCase;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ValidationExceptionTestAttribute implements ValidatorAttribute
{
    public function validate(mixed $value): void
    {
        throw new ValidationError('message');
    }
}

final class ValidationExceptionTestService implements Validator
{
    public function validate(mixed $value, array $args): void
    {
        throw new ValidationError(['message1', 'message2']);
    }
}

final class ValidationExceptionTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValidationExceptionHandling()
    {
        $dataObject = new class {
            #[ValidationExceptionTestAttribute]
            public string $property1 = '';

            #[Validate(ValidationExceptionTestService::class)]
            public string $property2 = '';
        };

        try {
            $this->process(
                [],
                $dataObject,
                [[ValidationExceptionTestService::class, new ValidationExceptionTestService()]],
            );
        } catch (ValidationFailedException $exception) {
            $this->assertEquals(
                [
                    'property1' => 'message',
                    'property2' => ['message1', 'message2'],
                ],
                $exception->errors,
            );
        }
    }
}
