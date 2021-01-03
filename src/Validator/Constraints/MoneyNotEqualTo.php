<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value not equal to the compared value.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyNotEqualTo extends AbstractMoneyComparison
{
    public const IS_EQUAL_ERROR = '6dcecf9b-093b-4342-8cf7-060a3ef55faa';

    /**
     * Maps error codes to the names of their constants.
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::IS_EQUAL_ERROR => 'IS_EQUAL_ERROR',
    ];

    public ?string $message = 'This value should not be equal to {{ compared_value }}.';
}
