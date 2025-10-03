<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Ignore a property.
 *
 * Targeted properties will not be populated, and the corresponding key must not
 * be present in the input array.
 *
 * Do not combine this attribute with any other Formotron attributes. The
 * behavior will be undefined.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Ignore {}
