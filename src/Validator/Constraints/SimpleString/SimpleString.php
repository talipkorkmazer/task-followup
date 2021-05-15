<?php

declare(strict_types=1);

namespace App\Validator\Constraints\SimpleString;

use Symfony\Component\Validator\Constraint;

/**
 * Class SimpleString.
 *
 * @Annotation
 */
class SimpleString extends Constraint
{
    public string $field = '';
}
