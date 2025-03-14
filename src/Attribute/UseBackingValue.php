<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Use backing value for a backed enum instead of name
 */

#[Attribute(Attribute::TARGET_PROPERTY)]
final class UseBackingValue {}
