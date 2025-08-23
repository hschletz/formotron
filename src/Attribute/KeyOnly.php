<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Report presence of a key only.
 *
 * If set on a property, it will receive a boolean value that indicates the
 * presence of the corresponding key in the input array. If present, the
 * corresponding value is discarded.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class KeyOnly {}
