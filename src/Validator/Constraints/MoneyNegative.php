<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value less than zero.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyNegative extends MoneyLessThan
{
    use MoneyZeroComparisonConstraintTrait;

    public string $message = 'This value should be negative.';
}
