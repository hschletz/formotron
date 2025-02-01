Formotron processes an array of input data into an application-defined data
object. Processing rules are defined by the data object itself, via property
types and custom attributes.

This documentation focuses on form data, but input data can be any associative
array, including but not limited to:
- submitted form data (`$_POST`)
- URI query parameters (`$_GET`)
- database records
- decoded JSON data


# Key features

- Easy to use, concise, reusable and self-documenting: Processing rules are
  defined by the data object itself, having all logic gathered in a single
  place.
- Always valid: The data object is instantiated, populated and validated in a
  single step. Invalid data will cause an exception. Calling code never sees an
  object in an invalid state.
- Extensible: Define your own processing rules and attach them to the data
  object via attributes.
- Integrates seamlessly: Write your own validators or use external validation
  libraries. Message formatting is left to the application, allowing usage of
  the applications's established i18n framework.
- DI friendly: Data processors and validators are instantiated from a container,
  allowing them to have arbitrary dependencies injected.
- Robust: Formotron leverages PHP features like property types, attributes and
  enums to have validation rules enforced at language level where possible.
  Formotron does not rely on docblock comments.


# Installation

Add Formotron to your project via composer:

```shell
composer require hschletz/formotron
```

Formotron requires an adequately configured
[PSR-11](https://www.php-fig.org/psr/psr-11/) compatible container to
instantiate transformers, validators and preprocessors. The concrete
implementation does not matter as long as it implements
`Psr\Container\ContainerInterface`. If your application already uses a
container, you can use just that.


# Usage

The main interface is the `Formotron\DataProcessor` class. First, set up a
PSR-11 container and pass it to the constructor:

```php
$dataProcessor = new \Formotron\DataProcessor($container);
```

Many containers are configured to resolve `Psr\Container\ContainerInterface` to
itself. With proper configuration (autowiring or a factory), the form processor
can be instantiated from the container:

```php
$dataProcessor = $container->get(\Formotron\DataProcessor::class);
```

In practice, the form processor will likely not be instantiated directly, but
injected into a class by the container:

```php
// MyClass will be instantiated by the container
class MyClass
{
    public function __construct(private \Formotron\DataProcessor $dataProcessor)
    {
    }
}
```

The `DataProcessor` class has a single public method `process()` which receives
the input data array and the name of a data object class. It returns a fully
populated instance of that class or throws a
`Formotron\AssertionFailedException` if input data is invalid.

```php
$data = $dataProcessor->process($_POST, FormData::class);
```


# Defining a data object

A data object is an object whose properties are populated from a corresponding
input field. By default, for every property defined in the class, a key with the
same name must be present in the input array. Its value will be assigned to the
property. Example:

```php
class DataObject
{
    public $foo;
    public $bar;
}

$input = [
    'foo' => 'value1',
    'bar' => 'value2',
];
$dataObject = $dataProcessor->process($input, DataObject::class);
```

The data object is constructed via reflection. Properties need not be declared
public. Protected and private properties can be populated too. A constructor is
bypassed.

It's strongly recommended to declare a data type for properties to enable
additional functionality and to avoid unexpected behavior. If a distinct type is
not suitable, a property can be explicitly declared as `mixed`. This has
advantages over untyped properties in regards to handling default values (see
below).



# Data type handling

All types can be declared nullable (i.e. `?string`), allowing NULL values in
addition to the type-specific rules below.

## string
String properties accept any input value that can be safely converted:
- string
- int, float (default PHP conversion, not locale-specific)
- objects with a `__toString()` method

## bool
Bool properties accept only `true` or `false`. Formotron does not attempt to
cast other values to `bool` because PHP's casting rules are error prone and may
not give the desired result. If you have non-boolean input values, they need to
be transformed first, preferrably with strict rules that match the particular
use case.

## array
Array properties accept only array input values. Types of array values are not
checked.

## enum
Enum properties accept enum instances, as well as strings/integers that match a
defined enum value. This is very useful for constraining values to a limited set
(for example, radio buttons and dropdowns with a static set of values). Valid
input values are defined by the type of enum:
- Basic enums accept the symbolic name (case sensitive).
- Backed enums accept the backing value only, but not the name.

```php
enum Basic
{
    case Foo;
    case Bar;
}

enum Backed: string
{
    case Foo = 'foo';
    case Bar = 'bar';
}

class DataObject
{
    public Basic $basic; // valid values: 'Foo', 'Bar', Basic::Foo, Basic::Bar
    public Backed $backed; // valid values: 'foo', 'bar', Backed::Foo, Backed::Bar
}
```

## class
Properties of a class type accept only instances of that class or a subclass.

## mixed
Properties declared as `mixed` or without a type accept any input value.

## Other types
Other types are not supported yet. They will be implemented in the future. Until
then, use `mixed` for any property with an unsupported type.


# Missing input fields and default values

The input array must contain keys for all properties defined in the data object.
Arrays with missing keys are invalid. This can be changed by setting a default
value:

```php
class DataObject
{
    public string $foo = 'bar';
}

$dataObject = $dataProcessor->process([], DataObject::class);
// valid, $foo will be 'bar'.
```

If the key is present, its value will be processed as usual.

For untyped properties, a limitation in the Reflection API prevents
distinguishing between an explicit and an implicit default of NULL. To avoid
bugs resulting from missing keys, Formotron does not support a default of NULL
for untyped properties, even where PHP would:
```php
class DataObject
{
    public $foo; // implicit default NULL
    public $bar = null;
}

$dataObject = $dataProcessor->process([], DataObject::class);
// Both keys would be treated as missing.
```

If you need a default of NULL and cannot declare a specific type, declare the
property as `mixed` which does not suffer from this limitation.


# Unmappable input fields

Input arrays with fields that cannot be mapped to a property in the data object
are invalid. This helps preventing bugs where input data processing may be
incomplete. If you need to ignore unmappable keys, remove them from the input
array first.


# Using different names for keys and properties

Sometimes, the direct mapping between property names and input array keys may
not be appropriate. A different name may be more meaningful in a different
context. The mapping can be changed by setting the `Key` attribute on a
property.

```php
use Formotron\Attribute\Key;

class DataObject
{
    #[Key('bar')]
    public string $foo;
}

$dataObject = $dataProcessor->process(['bar' => 'baz'], DataObject::class);
```

In this example, `$foo` will receive its value from the `bar` field. `foo` is
now an invalid key, unless mapped to a different property. This may become
confusing though, and is not recommended.


# Transforming values

Values from the input array may not be suitable for in-application
representation. Further processing is often necessary, like
- trimming surrounding whitespace from a string
- converting empty strings to NULL
- converting a timestamp string to a `DateTime` or `DateTimeImmutable` object
- fetching an object from a database using the input value as key

This is supported by attaching a transformer to a property. A transformer is a
class implementing the `Formotron\Transformer` interface:

```php
interface Transformer
{
    public function transform(mixed $value, array $args): mixed;
}
```

Implementations can do anything with the input value: leave it as is, modify it,
throw an exception, ... The returned value is still subject to the property's
validation rules.

Transformers are attached to a property via the `Transform` attribute. It takes
the name of a service – typically a class name – which will be pulled from the
container supplied to the DataProcessor's constructor. The container must
resolve the name to an object implementing the `Transformer` interface.

Additional arguments to the attribute are passed to the `transform()` method as
the `$args` parameter. See "Passing extra arguments to attributes" below.

```php
use Formotron\Attribute\Transform;

// The container must resolve Trim::class to an instance of this class.
class Trim implements Formotron\Transformer
{
    public function transform(mixed $value, array $args): mixed
    {
        if (!is_string($value)) {
            throw new Formotron\AssertionFailedException('not a string');
        }
        return trim($value);
    }
}

class DataObject
{
    #[Transform(Trim::class)]
    public string $foo;
}
```

Only one transformer can be attached to a single property. Otherwise the order
of execution could not be relied upon, and the outcome would become hard to
predict. If you need multple transformations, (for example, trim whitespace
first, only then convert an empty string to NULL), write a transformer that does
all transformations in a single step.

Transformers are run before validation. Only the output of the transfomer is
subject to validation rules, not the raw input value.

```php
use Formotron\Attribute\Transform;

class UserMapper implements Formotron\Transformer
{
    public function transform(mixed $value, array $args): mixed
    {
        // fetch_user_from_database() accepts a string/int key and returns
        // a corresponding User object, or throws an exception if the key is
        // invalid.
        return fetch_user_from_database($value);
    }
}

class DataObject
{
    #[Transform(UserMapper::class)]
    public User $user;
}
```

In this example, the `user` key must hold a string/int value (whatever the
database uses as key), and the data object will receive the resulting object,
which must be of the `User` class or a subclass of `User`.

Because validation occurs only after transformation, transformer implementations
must account for potentially invalid data.


# Validating values

Input data often comes from an untrusted source and must be validated before
being consumed by the application. Even with trusted sources, sanity checks
improve application robustness.

Basic validation is already achieved by declaring properties with a datatype.
Formotron (and, as a last resort, PHP itself) will guarantee that the property
is populated with a value that fits its datatype, or can be safely converted.

Validation often goes beyond simple type checks. An input value for a string
property may be a string, but not satisfy a length constraint. A value may be
constrained to a list of valid values that, unlike enums, is determined
dynamically at runtime.

Validation rules can become rather complex. Arbitrary rules can be checked by a
validator. A validator is a class implementing the `Formotron\Validator` interface:

```php
interface Validator
{
    public function getValidationErrors(mixed $value, array $args): array;
}
```

Implementations receive the input value with all transformations already
applied, do all necessary checks and return the result. The return value is a
list of validation errors. If the input value is valid, an empty array must be
returned. The input value may fail more than one check, in which case multiple
errors can be reported simultaneously.

Individual error values can be anything like a simple string, a symbolic error
code, or an error object holding a message template and message arguments.
Errors are currently not further evaluated, so a simple debug message may be
sufficient at the moment. Support for detailed error reporting is planned for
the future. Until then, a validator may throw a custom exception which supplies
datailed error information and can be caught and evaluated by the calling code.

Validators are attached to a property via the `Assert` attribute. It takes the
name of a service – typically a class name – which will be pulled from the
container supplied to the DataProcessor's constructor. The container must
resolve the name to an object implementing the `Validator` interface.

Additional arguments to the attribute are passed to the `getValidationErrors()`
method as the `$args` parameter. See "Passing extra arguments to attributes"
below.

```php
use Formotron\Attribute\Assert;

// The container must resolve MaxLength::class to an instance of this class.
class MaxLength implements Formotron\Validator
{
    public function getValidationErrors(mixed $value, array $args): array
    {
        if (is_string($value) && mb_strlen($value) <= 100) {
            return [];
        } else {
            return ['Maximum length exceeded'];
        }
    }
}

class DataObject
{
    #[Assert(MaxLength::class)]
    public string $foo;
}
```

Multiple validators can be attached to a single property, but the order of
execution should not be relied upon. If order is significant, write a validator
that does all necessary checks in a single step.

Unlike similar packages, Formotron does not ship with any validator
implementations, but only provides the validation framework. Many validators are
trivial and easy to implement. You can still use an external validation library
and wrap its functions in a `Validator` object.


# Preprocessing input data

Transformers and validators operate on individual data object properties and
their associated input array elements, isolated and independent of each other.
They have no knowledge of the context in which the input value was submitted.

Sometimes this is not sufficient. Some processing rules need access to the whole
input array, or the array needs modification before individual fields can be
processed. Common operations include
- removing keys that are not mapped to the data object
- providing non-static default values for missing keys
- transformations or validations that depend on more than one field

This could be done manually before calling `DataProcessor::process()`, but that
would have several drawbacks:
- There is now a contract to follow, which must be explicitly documented and
  enforced.
- The extra processing is disconnected from the data object. Everything else is
  defined in the data object class via property types and attributes. While the
  actual implementation is located in transformer and validator objects, the
  general processing logic is outlined in the data object class, but the
  preprocessing logic is not.
- The calling code becomes more complex and difficult to test.

Instead, preprocessing should be done in a class that implements the
`Formotron\PreProcessor` interface:

```php
interface PreProcessor
{
    public function process(array $formData): array;
}
```

Implementations receive the input array (or the output of another preprocessor)
and return an array that will be fed to the next preprocessor (if any) and
finally used to populate the data object. The implementation can do anything:
add or remove keys, modify values, replace the whole array, validate data and
throw exceptions, ...

Preprocessors are attached to the data object class via the `PreProcess`
attribute. It takes the name of a service – typically a class name – which will
be pulled from the container supplied to the DataProcessor's constructor. The
container must resolve the name to an object implementing the `PreProcessor`
interface.

Multiple preprocessors can be attached to a single class, but the order of
execution should not be relied upon. If order is significant, write a
preprocessor that does all necessary operations in a single step.

The following example demonstrates the use of a preprocessor for CSRF
protection. The form data contains a token, which is compared to the token
stored in session data. (The actual implementation is not provided here.) Once
validation has succeeded, the token is no longer useful and therefore not part
of the data object.

```php
use Formotron\Attribute\PreProcess;

// The container must resolve CsrfProtection::class to an instance of this class.
class CsrfProtection implements Formotron\PreProcessor
{
    public function process(array $formData): array
    {
        if (!is_token_valid($formData['token'] ?? '')) {
            throw new Formotron\AssertionFailedException('invalid token');
        }
        // The 'token' key is not mapped to a property and must be removed.
        unset($formData['token']);
        return $formData;
    }
}

#[PreProcess(CsrfProtection::class)]
class DataObject
{
    // Only fields of interest are defined here.
    // The token is only relevant for the preprocessor.
    public string $foo;
}
```


# Passing extra arguments to attributes

Extra arguments to the `Transform` and `Assert` attributes are passed to the
`transform()`/`getValidationErrors()` method, allowing generic implementations
with parameters.

```php
class DataObject
{
    #[Transform(ToBoolTransformer::class), 'yes', 'no']
    public bool $foo;
}

class ToBoolTransformer implements Transformer
{
    public function transform(mixed $value, array $args): mixed
    {
        [$trueValue, $falseValue] = $args;
        return match ($value) {
            $trueValue => true,
            $falseValue => false,
        };
    }
}
```

Named arguments are also possible:

```php
class DataObject
{
    #[Transform(ToBoolTransformer::class), trueValue: 'yes', falseValue: 'no']
    public bool $foo;
}

class ToBoolTransformer implements Transformer
{
    public function transform(mixed $value, array $args): mixed
    {
        return match ($value) {
            $args['trueValue'] => true,
            $args['falseValue'] => false,
        };
    }
}
```

The arguments are not defined in code. Arbitrary names and values may be passed.
The transformer/validator may have to validate its arguments, editors cannot
provide hints and autocompletion, and code analysis tools cannot check types, or
may even complain about undefined argument names.

To provide language-level definitions for attribute arguments, create an
attribute class which extends the `Transform`/`Assert` attribute, and define the
arguments in its constructor. Argument types can be anything that is allowed for
attribute arguments, and they can have default values.

The service name does not have to be provided as a parameter. If the attribute
is only used in conjunction with a particular transformer/validator, the service
name can be hardcoded in the constructor.

```php

#[Attribute(Attribute::TARGET_PROPERTY)]
class ToBool extends Transform
{
    public function __construct(mixed $trueValue, mixed $falseValue)
    {
        parent::__construct(ToBoolTransfomer::class, trueValue: $trueValue, falseValue: $falseValue);
    }
}

class DataObject
{
    #[ToBool(trueValue: 'yes', falseValue: 'no']
    public bool $foo;
}
```

A strict constructor signature provides basic validation of arguments.
Additional validation might be necessary, either in the attribute constructor or
in the invoked method. Keep in mind that attribute arguments are not user input,
but part of the code. Invalid arguments are a bug in the code that uses the
attribute, not a runtime issue.

If there is a chance that the transformer/validator may be used directly, i.e.
not via an attribute, it should validate its arguments more thoroughly,
particularly if its extra arguments may contain user input.


# Error handling

## Handling logical errors

Most errors reported by Formotron are failed sanity checks where input data is
formally incorrect: a value has an unsuitable data type or does not map to an
enum. This typically means that something went seriously wrong: A bug in the
backend, or malformed input data that could not have originated from the
expected form.

These errors are reported via a `Formotron\AssertionFailedException`.
Transformers, validators and preprocessors can also throw this exception for
hard errors. In fact, Formotron does not catch any exceptions, so it's possible
to throw anything that is appropriate.

End-users are not expected to see these errors as a response to incorrect user
action, like leaving a required field empty. In most cases, no special handling
is required for these exceptions. The application's default exception handler is
probably the most appropriate tool to handle them. This is a response to a bug
or a technical error, and a standard message, optionally with a backtrace, will
convey more useful debugging information than a beautifully formatted error
message.

## Handling invalid user input

Not all errors are caused by malfunction. Many forms are not immune to user
errors: some required fields could be left blank or an entered string may be too
long or too short.

Throwing an exception without special handling would be inappropriate for this
kind of error. Invalid user input is typically handled by collecting errors and
re-displaying the form with error messages.

Formotron does not yet support advanced error handling, but you can implement
your own. You could throw a custom exception in a transformer, validator or
preprocessor, and catch it:

```php
try {
    $formData = $dataProcessor->process($input, DataObject::class);
} catch (MyCustomException $exception) {
    // Handle invalid user input
}
```

This has the disadvantage of aborting on the first error. Additional errors
would stay unnoticed until the form is re-submitted with valid data for the
first field. As an alternative, instead of throwing an exception, you could
collect errors somewhere and continue, and evaluate the errors afterwards.
However, this breaks the promise of an always valid data object, and there is
now a contract to follow. This can be avoided by wrapping this kind of error
handling in a reusable method:

```php
function process(array $input, string $className)
{
    $dataObject = $this->dataProcessor->process($input, $className);
    $errors = $this->getErrors(); // retrieve collected errors
    if ($errors) {
        // Report detailed errors to calling code
        throw new ValidationException($errors);
    }
    return $dataObject;
}
```

This wrapper returns a valid data object or throws an exception for invalid
input. Calling code can catch the special exception and evaluate the detailed
error information contained wihin.

## Taking advantage of frontend form validation

Modern web applications often validate form data by themselves, either while
typing or immediately before submission. While this frontend validation enhances
the user experience, it does not remove the necessity for thorough backend
validation. The submitted form data is still untrusted, and some validations can
only be done in the backend.

However, with frontend validation in place, backend error handling can be
simplified. When it can be assumed that the data has already been validated once
and validation errors have been gracefully handled in the frontend, validation
failures in the backend can be treated as unexpected errors, because something
went wrong:

- Frontend validation has somehow been bypassed, which should not happen if the
  application is used as intended.
- Mismatch between validation rules, the backend rules are more strict than the
  frontend rules.
- Some other bug.

These unexpected situations have to be handled, but they don't have to be
handled nicely. Like with logical errors, simply throwing an exception and have
regular exception handling deal with it may be the best choice. Complex detailed
handling of invalid user input is only necessary for failures that are not
covered in the frontend.

There are many JavaScript libraries for validation in the frontend, but HTML
form elements already provide attributes for basic constraints that are followed
by modern browsers and only need to be verified in the backend.

# Handling HTML form data

Submitted HTML form data can be straightforward (most elements produce just a
string that contains the entered value), but sometimes surprising and annoying.
Knowing how form element values are represented can make transformation and
validation in Formotron quite simple.

Form data is generally transmitted as a map of string values. Many elements
constrain the format of their values by their type. Additional constraints can
be applied by HTML attributes. These constraints are an excellent starting point
for validation in the backend, and it is recommended to make use of them as much
as possible. Anything that does not fullify these constraints can be handled as
an unexpected error. More sophisticated error handling is mostly useful for
additional constraints that cannot be expressed in HTML.

The procedures below only apply to single values. If a form element's name ends
with `[]`, the result will be an array of strings. Formotron can only verify
that the input value is an array. The array values themselves are not validated
automatically. This must be done by a custom validator that iterates over the
array and validates the values according to the rules below.

Most HTML inputs support the `required` attribute which will by default prevent
form submission if no value is entered. The backend can assume required fields
to be present and non-empty. Formotron will throw an exception if the key is
missing and the data object property has no default value. As with all frontend
validation, it cannot be relied upon on its own.

Frontend validation via HTML attributes only affects default form submission.
JavaScript code may send form data bypassing builtin validations.

## Text, password, search and telephone inputs
`<input type="text">` produces a string with the element's value. The
`minlength`, `maxlength` and `pattern` attributes can constrain the content.

`<input type="password">`, `<input type="search">` and `<input type="tel">`
behave the same way in regards to submitted data. The only difference is how
browsers may style the element and control interaction with the user.

`<textarea>` supports only the `minlength` and `maxlength` attributes, but not
`pattern`.

## Number and range inputs
`<input type="number">` produces a string that can be parsed as a number or an
empty string. The `max`, `min` and `step` attributes further constrain the range
of possible values.

The number is represented in a non-localized format without thousands separator.
Integer values for the `step` attribute (default is 1) allow integers only.
Fractional `step` values allow integer and fractional input values, the latter
with a dot as fractional separator, regardless of locale.

`<input type="range">` behaves identically in regards to submitted data. The
only difference is how browsers display the element.

## Hidden elements
`<input type="hidden">` produces a string with the element's value.
Interpretation is up to the application. There are no client-side constraints.

## Checkboxes

`<input type="checkbox">` produces a string with the element's `value` attribute
if the checkbox is checked. The field will be missing completely otherwise.
Generating a single checked/unchecked value is more complex than for other
element types.

Some applications and frameworks deal with this problem by adding a hidden
element to the form with the same name as the checkbox element and the unchecked
value, so that the key will always be present. This moves the logic to the form
markup, which is not the best place for this implementation detail.

Formotron can handle checkbox data (and the lack thereof) without this hack. To
handle the missing key, the unchecked value must be set as default on the
property. A transformer must handle the checked value.

```php
use Formotron\Attribute\Transform;

class Exists implements Formotron\Transformer
{
    public function transform(mixed $value, array $args): mixed
    {
        // This is only encountered if the checkbox is checked, so we can ignore
        // the actual value unless this transformer may be used in a different
        // context.
        return true;
    }
}

class DataObject
{
    #[Transform(Exists::class)]
    public bool $foo = false; // Default to unchecked value to allow missing key
}
```

Checkboxes are often mapped to `true`/`false`, but any other pair of values can
be used the same way. When using a string, the transformer may not be necessary,
but the checked value must be validated. Alternatively, an enum with 2 values
can be used without transformers and validators:

```php
enum Checkbox
{
    case Checked; // The checkbox element must set its value attribute to "Checked"
    case Unchecked;
}

class DataObject
{
    public Checkbox $foo = Checkbox::Unchecked;
}
```

## Radio buttons
`<input type="radio">` produces a string with the value of the selected element,
or no data at all if no button was selected. The latter can only happen if no
button was preselected via the `checked` attribute. In that case the missing
array key must be accounted for, for example with a default value for the data
object property.

Radio button groups with a static set of values can be easily mapped to an enum.
If a string property is used or the set of values is generated at runtime,
validation must be done manually.

## Dropdowns
`<select>` produces a string with the value of the selected entry. Options with
a static set of values can be easily mapped to an enum. If a string property is
used or the set of values is generated at runtime, validation must be done
manually.

One option is always selected - the first if no option has the `selected`
attribute. An empty option gives the illusion of no selection, but its value (or
an empty string when it has no `value` attribute) is submitted. This must be
accounted for when processing the value. The appropriate strategy depends on
your use case. The most straightforward strategies are:

- Define an enum, with the "empty" value as a member. Empty strings are
  supported by backed enums only, other values can be mapped to any enum type.
- Define the data object property as nullable, and apply a transformer to
  convert the "empty" value to NULL.

## E-mail addresses
`<input type="email">` produces a string that contains a single e-mail address,
a comma-separated list of addresses (if the `multiple` attribute is set) or an
empty string. The `minlength`, `maxlength` and `pattern` attributes can
constrain the value further.

Input is validated to be a syntactically correct e-mail address, but the address
is not guaranteed to exist. The validation ruleset may also be incomplete.
Validating e-mail addresses is tricky, with some non-obvious rules and edge
cases. It is recommended to wrap an existing validation library (don't write
your own, it will most likely be incorrect) in a `Validator` class and handle
failures more gracefully than just throwing an exception.

## URL
`<input type="url">` produces a string that contains a syntactically valid URL or an
empty string. The `minlength`, `maxlength` and `pattern` attributes can
constrain the value further.

Validation is limited to syntax checking. The URL does not have to lead to an existing ressource, or even contain a meaningful scheme.
The backend may need to validate the URL more thoroughly:

- Check for adequate scheme (`http` or `https` in most cases)
- Try to contact the ressource (for example, try to make a HTTP request)

Failures should be handled more gracefully than just throwing an exception.

## Date/time pickers
Date/time pickers produce a string that can parsed with the formats below, or an
empty string if no value was entered. The `max`, `min` and `step` attributes
further constrain the range of possible values.

- `<input type="date">`: `Y-m-d`
- `<input type="time">`: `H:i` or `H:i:s` depending on the `step` attribute
- `<input type="datetime-local">` `Y-m-d\TH:i` or `Y-m-d\TH:i:s` depending on
  the `step` attribute


## Month/week pickers
`<input type="month">` and `<input type="week">` are not recommended because
Firefox will just show a text input that allows entering arbitrary text. With
browsers that support it, they produce a string that can be parsed as `Y-m`
(month) or `Y-\WW` (week) or an empty string. The `max`, `min` and `step`
attributes further constrain the range of possible values. Because of the
possibility of invalid input, validation failure should be handled more
gracefully than just by throwing an exception.

## Color pickers
`<input type="color">` produces a hash sign followed by a 6-digit hexadecimal
RGB value, like `#aabbcc`, or an empty string.

## Submit buttons
`<input type="submit">` does not produce any data by default. If a `name`
attribute is provided. it will be present as a key, with the button's `value`
(or default text, if missing). However, the double role of the `value` attribute
as a label and form data is bad design, particularly with multilingual user
interfaces.

`<button type="submit">` is a better alternative. It does not produce any data
by default. The `name` and `value` attributes behave the same way, but `value`
is used only for form data (empty by default), not as a label.

Either way, the `name` attribute should only be set to distinct multiple
buttons. With only 1 button, the resulting form data element would just be
meaningless noise which has to be removed before processing. By using multiple
buttons with the same `name` but different `value`, it is possible to determine
which button was clicked and take appropriate action:

```html
<button type="submit" name="action" value="this">Do This</button>
<button type="submit" name="action" value="that">Do That</button>
```

Now the `action` field can be evaluated to determine what to do with the form
data. An enum is a simple way to represent and validate the action.

It might be even simpler to assign different handlers to the buttons via the
`formaction` attribute and keep the button out of the form data (no `name`
attribute).


## Other elements
Some elements do not produce any form data and are mentioned here for
completeness only:
- `<input type="file">` is only useful for forms with `multipart/formdata`
  encoding, in which case uploaded files will be available via `$_FILES` and
  handled entirely different. With this encoding, `$_POST` will not contain any
  data for this element.
- `<input type="image">` adds the clicked coordinates of the image in the `x`
  and `y` keys, optionally prefixed by the `name` attribute. Not very useful
  unless you want to implement some sort of click map.
- `<input type="reset">` does not produce any form data.
