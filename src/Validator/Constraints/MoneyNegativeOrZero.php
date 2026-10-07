<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value less than or equal to zero.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyNegativeOrZero extends MoneyLessThanOrEqual
{
    use MoneyZeroComparisonConstraintTrait;

    public string $message = 'This value should be either negative or zero.';
}
