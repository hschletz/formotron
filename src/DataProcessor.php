<?php

namespace Formotron;

use BackedEnum;
use Formotron\Attribute\Key;
use Formotron\Attribute\PreProcess;
use Formotron\Attribute\TransformerAttribute;
use Formotron\Attribute\TransformerServiceAttribute;
use Formotron\Attribute\UseBackingValue;
use Formotron\Attribute\ValidatorAttribute;
use Formotron\Attribute\ValidatorServiceAttribute;
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
final class DataProcessor
{
    public function __construct(private ContainerInterface $container) {}

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
     * @param iterable<mixed[]> $input
     * @param class-string<T> $className
     * @return iterable<T>
     */
    public function iterate(iterable $input, string $className): iterable
    {
        foreach ($input as $element) {
            yield $this->process($element, $className);
        }
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
            $this->processValidators($property, $value);
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
                    if (
                        (is_string($value) || $value instanceof Stringable) &&
                        preg_match('/^[+-]?[0-9]+$/', (string) $value)
                    ) {
                        $value = (int) (string) $value;
                    } elseif (!is_int($value)) {
                        throw new AssertionFailedException(sprintf(
                            'Value for $%s has invalid type, expected int or parseable string, got %s',
                            $key,
                            gettype($value),
                        ));
                    }
                    break;
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
                        $value = $this->parseToEnum($typeName, $key, $value, $property);
                    } elseif (class_exists($typeName) || interface_exists($typeName)) {
                        if (!$value instanceof $typeName) {
                            throw new AssertionFailedException(sprintf(
                                'Value for $%s has invalid type, expected %s, got %s',
                                $key,
                                $typeName,
                                gettype($value),
                            ));
                        }
                    } else {
                        // "object", "self", "parent"
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
        // An attribute implementing both interfaces would appear twice in the
        // resulting array. array_unique() allows distiguishing from multiple
        // attributes.
        $attributes = array_unique(array_merge(
            $property->getAttributes(TransformerAttribute::class, ReflectionAttribute::IS_INSTANCEOF),
            $property->getAttributes(TransformerServiceAttribute::class, ReflectionAttribute::IS_INSTANCEOF),
        ));
        if (count($attributes) > 1) {
            throw new LogicException('Only 1 transformer can be attached to a property');
        }
        $instance = ($attributes[0] ?? null)?->newInstance();
        if ($instance instanceof TransformerAttribute) {
            if ($instance instanceof TransformerServiceAttribute) {
                throw new LogicException(sprintf(
                    'Attribute %s must implement %s or %s, but not both',
                    get_class($instance),
                    TransformerAttribute::class,
                    TransformerServiceAttribute::class,
                ));
            }
            /** @var mixed */
            $value = $instance->transform($value);
        } elseif ($instance instanceof TransformerServiceAttribute) {
            $service = $instance->getServiceName();
            $transformer = $this->container->get($service);
            if (!$transformer instanceof Transformer) {
                throw new LogicException("Service {$service} does not implement " . Transformer::class);
            }
            /** @var mixed */
            $value = $transformer->transform($value, $instance->getArguments());
        }

        return $value;
    }

    /**
     * @param class-string<UnitEnum> $typeName
     */
    private function parseToEnum(string $typeName, string $key, mixed $value, ReflectionProperty $property): UnitEnum
    {
        if ($value instanceof $typeName) {
            /** @var UnitEnum $value */
            return $value;
        }
        $enum = new ReflectionEnum($typeName);
        $backingType = $enum->getBackingType();
        if ($backingType && $property->getAttributes(UseBackingValue::class)) {
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
            // Pure enum or backed enum without the UseBackingValue set on the
            // property. $value is interpreted as name.
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

    private function processValidators(ReflectionProperty $property, mixed $value): void
    {
        $attributes = array_merge(
            $property->getAttributes(ValidatorAttribute::class, ReflectionAttribute::IS_INSTANCEOF),
            $property->getAttributes(ValidatorServiceAttribute::class, ReflectionAttribute::IS_INSTANCEOF),
        );
        foreach ($attributes as $attribute) {
            /** @var ValidatorAttribute | ValidatorServiceAttribute */
            $instance = $attribute->newInstance();
            if ($instance instanceof ValidatorAttribute) {
                if ($instance instanceof ValidatorServiceAttribute) {
                    throw new LogicException(sprintf(
                        'Attribute %s must implement %s or %s, but not both',
                        get_class($instance),
                        ValidatorAttribute::class,
                        ValidatorServiceAttribute::class,
                    ));
                }
                $instance->validate($value);
            } else {
                $service = $instance->getServiceName();
                $validator = $this->container->get($service);
                if (!$validator instanceof Validator) {
                    throw new LogicException("Service {$service} does not implement " . Validator::class);
                }
                $validator->validate($value, $instance->getArguments());
            }
        }
    }

    private function isStringable(mixed $value): bool
    {
        return is_string($value) || is_int($value) || is_float($value) || $value instanceof Stringable;
    }
}
