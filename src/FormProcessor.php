<?php

namespace Formotron;

use BackedEnum;
use Formotron\Attribute\Assert;
use Formotron\Attribute\Key;
use Formotron\Attribute\PreProcess;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use UnitEnum;
use ValueError;

/**
 * Creates a data object and populates it with input data.
 *
 * USAGE:
 *
 * Define a class with desired properties:
 *
 *     class FormData
 *     {
 *         public $foo;
 *         public $bar;
 *     }
 *
 * Pass input data and the class name to the FormProcessor instance:
 *
 *     $formData = $formProcessor->process(
 *         [
 *             'foo' => 'baz',
 *             'bar' => 'foobar',
 *         ],
 *         FormData::class
 *     );
 *
 * Processing occurs according to property's data types and extra rules defined
 * by attributes.
 */
class FormProcessor
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @template T of object
     * @param mixed[] $input
     * @param class-string<T> $className
     * @return T
     */
    public function process(array $input, string $className): object
    {
        $class = new ReflectionClass($className);
        $input = $this->preProcess($input, $class);
        $object = $this->createObject($input, $class);

        return $object;
    }

    /**
     * @template T of object
     * @param mixed[] $input
     * @param ReflectionClass<T> $class
     * @return mixed[]
     */
    private function preProcess(array $input, ReflectionClass $class): array
    {
        foreach ($class->getAttributes(PreProcess::class) as $attribute) {
            $service = $attribute->newInstance()->preProcessorService;
            $preProcessor = $this->container->get($service);
            if (!$preProcessor instanceof PreProcessor) {
                throw new LogicException("Service {$service} does not implement " . PreProcessor::class);
            }

            $input = $preProcessor->process($input);
        }

        return $input;
    }

    /**
     * @template T of object
     * @param mixed[] $input
     * @param ReflectionClass<T> $class
     * @return T
     */
    private function createObject(array $input, ReflectionClass $class): object
    {
        $instance = $class->newInstanceWithoutConstructor();
        $processedKeys = [];
        foreach ($class->getProperties() as $property) {
            $keyAttribute = $property->getAttributes(Key::class)[0] ?? null;
            $key = $keyAttribute ? $keyAttribute->newInstance()->key : $property->getName();
            /** @psalm-suppress MixedAssignment */
            $value = $this->getValue($property, $key, $input);
            $this->processAssertions($property, $value);
            $property->setValue($instance, $value);
            $processedKeys[] = $key;
        }

        $extraKeys = array_diff(array_keys($input), $processedKeys);
        if ($extraKeys) {
            throw new AssertionFailedException('Input data contains extra keys: ' . implode(', ', $extraKeys));
        }

        return $instance;
    }

    /**
     * @param mixed[] $input
     */
    private function getValue(ReflectionProperty $property, string $key, array $input): mixed
    {
        if (array_key_exists($key, $input)) {
            /** @psalm-suppress MixedAssignment */
            $value = $this->processValue($property, $key, $input[$key]);
        } elseif ($property->hasType()) {
            if ($property->hasDefaultValue()) {
                /** @psalm-suppress MixedAssignment */
                $value = $property->getDefaultValue();
            } else {
                throw new AssertionFailedException('Missing key: ' . $key);
            }
        } else {
            /** @psalm-suppress MixedAssignment */
            $value = $property->getDefaultValue();
            if ($value === null) {
                throw new AssertionFailedException('Missing key: ' . $key);
            }
        }

        return $value;
    }

    private function processValue(ReflectionProperty $property, string $key, mixed $value): mixed
    {
        $type = $property->getType();
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            switch ($typeName) {
                case 'string':
                case 'bool':
                case 'int':
                case 'float':
                case 'iterable':
                    throw new LogicException("Handling of $typeName properties is not implemented yet");
                case 'array':
                    if (!is_array($value)) {
                        throw new AssertionFailedException(sprintf(
                            'Value for $%s has invalid type, expected array, got %s',
                            $key,
                            gettype($value),
                        ));
                    }
                    break;
                case 'mixed':
                    break;
                default:
                    if (enum_exists($typeName)) {
                        /** @var class-string<UnitEnum> $typeName */
                        if (!is_string($value) && !is_int($value)) {
                            throw new AssertionFailedException(sprintf(
                                'Value for $%s has invalid type, expected string|int, got %s',
                                $key,
                                gettype($value),
                            ));
                        }
                        $value = $this->parseToEnum($typeName, $key, $value);
                    } else {
                        // "object", "self", "parent", class, interface
                        throw new LogicException('Cannot handle properties of type ' . $typeName);
                    }
            }
        } elseif ($type !== null) {
            throw new LogicException('Union/intersection types are not supported');
        }

        return $value;
    }

    /**
     * @param class-string<UnitEnum> $typeName
     */
    private function parseToEnum(string $typeName, string $key, string | int $value): UnitEnum
    {
        $enum = new ReflectionEnum($typeName);
        $backingType = $enum->getBackingType();
        if ($backingType) {
            // Backed enum, $value is interpreted as backing value
            if ((string) $backingType == 'int' && is_string($value)) {
                if (ctype_digit($value)) {
                    $value = (int) $value;
                } else {
                    throw new AssertionFailedException("Invalid value for \$$key: $value");
                }
            }
            try {
                /** @var class-string<BackedEnum> $typeName */
                return $typeName::from($value);
            } catch (ValueError) {
                throw new AssertionFailedException("Invalid value for \$$key: $value");
            }
        } else {
            // Pure enum, $value is interpreted as name
            if (!is_string($value)) {
                throw new AssertionFailedException(sprintf(
                    'Value for $%s has invalid type, expected string, got %s',
                    $key,
                    gettype($value),
                ));
            }
            if ($enum->hasCase($value)) {
                return $enum->getCase($value)->getValue();
            } else {
                throw new AssertionFailedException("Invalid value for \$$key: $value");
            }
        }
    }

    private function processAssertions(ReflectionProperty $property, mixed $value): void
    {
        foreach ($property->getAttributes(Assert::class) as $attribute) {
            $assertion = $attribute->newInstance();
            $validator = $this->container->get($assertion->validatorService);
            if (!$validator instanceof Validator) {
                throw new LogicException("Service {$assertion->validatorService} does not implement " . Validator::class);
            }
            $errors = $validator->getValidationErrors($value);
            if ($errors) {
                throw new AssertionFailedException(sprintf(
                    'Assertion %s failed on $%s',
                    $assertion->validatorService,
                    $property->getName(),
                ));
            }
        }
    }
}
