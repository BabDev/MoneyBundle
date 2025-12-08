<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value not equal to the compared value.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyNotEqualTo extends AbstractMoneyComparison
{
    public const string IS_EQUAL_ERROR = '6dcecf9b-093b-4342-8cf7-060a3ef55faa';

    protected const array ERROR_NAMES = [
        self::IS_EQUAL_ERROR => 'IS_EQUAL_ERROR',
    ];

    public ?string $message = 'This value should not be equal to {{ compared_value }}.';
}
