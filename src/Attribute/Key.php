<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Set alternative input key for property.
 *
 * By default, a key with each property's name is looked up in the input array.
 * Set this attribute on a property to look for a different key. If the
 * attribute is set, the property's name must not occur as an input key unless
 * it is set as a Key attribute on a different property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Key
{
    public function __construct(public readonly string $key) {}
}
