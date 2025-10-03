<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Set key mapper on class.
 *
 * Set this attribute on a class to define the mapping between property names
 * and input array keys. The provided argument is passed to the container, which
 * must resolve to an object implementing the @see Formotron\KeyMapper
 * interface.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MapKeys
{
    public function __construct(public readonly string $keyMapperService) {}
}
