<?php

declare(strict_types=1);

namespace App\Validator\Constraints\SimpleString;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class SimpleStringValidator.
 */
class SimpleStringValidator extends ConstraintValidator
{
    private const MESSAGE = '{{ field }} may only contain the following characters: A-Z, a-z, 0-9, space, -, _, ., :, ;, !, +, ?, (, ), [, ], |, & and /';
    private const SIMPLE_STRING_REGEX = '/^[\p{L}a-z0-9_\+\-\s\:\/\.&;\(\)\!\?\'\|\[\]]+$/imu';

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleString) {
            throw new UnexpectedTypeException($constraint, SimpleString::class);
        }

        if (is_string($value)) {
            $this->validateString($value, $constraint);
            return;
        }

        if (null !== $value) {
            $this->context
                ->buildViolation('{{ field }} must be a string value')
                ->setParameter('{{ field }}', $constraint->field)
                ->addViolation();
        }
    }

    /**
     * @param string $value
     * @param Constraint $constraint
     *
     * @return void
     */
    private function validateString(string $value, Constraint $constraint): void
    {
        if ($value === '') {
            return;
        }

        if (!preg_match(self::SIMPLE_STRING_REGEX, $value, $matches)) {
            $this->context
                ->buildViolation(self::MESSAGE)
                ->setParameter('{{ field }}', $constraint->field)
                ->addViolation();
        }
    }
}
