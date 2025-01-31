<?php

namespace Formotron;

use BackedEnum;
use Formotron\Attribute\Assert;
use Formotron\Attribute\Key;
use Formotron\Attribute\PreProcess;
use Formotron\Attribute\Transform;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use Stringable;
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
 * Pass input data and the class name to the DataProcessor instance:
 *
 *     $formData = $dataProcessor->process(
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
class DataProcessor
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
        /** @var mixed */
        $value = $this->transformValue($property, $value);
        $type = $property->getType();
        if ($type instanceof ReflectionNamedType) {
            if ($value === null && $type->allowsNull()) {
                return $value;
            }
            $typeName = $type->getName();
            switch ($typeName) {
                case 'string':
                    if ($this->isStringable($value)) {
                        // @phpstan-ignore cast.string (condition guarantees safe cast of mixed value)
                        $value = (string) $value;
                    } else {
                        throw new AssertionFailedException(sprintf(
                            'Value for $%s has invalid type, expected string|int|float|Stringable, got %s',
                            $key,
                            gettype($value),
                        ));
                    }
                    break;
                case 'bool':
                    if (!is_bool($value)) {
                        throw new AssertionFailedException(sprintf(
                            'Value for $%s has invalid type, expected bool, got %s',
                            $key,
                            gettype($value),
                        ));
                    }
                    break;
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
                        $value = $this->parseToEnum($typeName, $key, $value);
                    } elseif (class_exists($typeName)) {
                        if (!$value instanceof $typeName) {
                            throw new AssertionFailedException(sprintf(
                                'Value for $%s has invalid type, expected %s, got %s',
                                $key,
                                $typeName,
                                gettype($value),
                            ));
                        }
                    } else {
                        // "object", "self", "parent", interface
                        throw new LogicException('Cannot handle properties of type ' . $typeName);
                    }
            }
        } elseif ($type !== null) {
            throw new LogicException('Union/intersection types are not supported');
        }

        return $value;
    }

    private function transformValue(ReflectionProperty $property, mixed $value): mixed
    {
        $transformAttribute = $property->getAttributes(Transform::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if ($transformAttribute) {
            $instance = $transformAttribute->newInstance();
            $service = $instance->transformerService;
            $transformer = $this->container->get($service);
            if (!$transformer instanceof Transformer) {
                throw new LogicException("Service {$service} does not implement " . Transformer::class);
            }
            /** @var mixed */
            $value = $transformer->transform($value, $instance->args);
        }

        return $value;
    }

    /**
     * @param class-string<UnitEnum> $typeName
     */
    private function parseToEnum(string $typeName, string $key, mixed $value): UnitEnum
    {
        if ($value instanceof $typeName) {
            return $value;
        }
        $enum = new ReflectionEnum($typeName);
        $backingType = $enum->getBackingType();
        if ($backingType) {
            // Backed enum, $value is interpreted as backing value
            // @phpstan-ignore match.unhandled (unmatched value not to be expected)
            $value = match ((string) $backingType) {
                'int' => is_int($value) || is_string($value) && ctype_digit($value) ? (int) $value : throw new AssertionFailedException(
                    sprintf(
                        'Value for $%s has invalid type, expected int|int-string, got %s',
                        $key,
                        gettype($value),
                    )
                ),
                // @phpstan-ignore cast.string (condition guarantees safe cast of mixed value)
                'string' => $this->isStringable($value) ? (string) $value : throw new AssertionFailedException(
                    sprintf(
                        'Value for $%s has invalid type, expected stringable, got %s',
                        $key,
                        gettype($value),
                    )
                ),
            };
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

    private function isStringable(mixed $value): bool
    {
        return is_string($value) || is_int($value) || is_float($value) || $value instanceof Stringable;
    }
}
