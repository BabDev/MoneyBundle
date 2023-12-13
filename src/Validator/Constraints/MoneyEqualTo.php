<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value equal to the compared value.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyEqualTo extends AbstractMoneyComparison
{
    public const NOT_EQUAL_ERROR = '0057eef9-7cbd-43fc-b0ca-bb3b7a82567f';

    protected const ERROR_NAMES = [
        self::NOT_EQUAL_ERROR => 'NOT_EQUAL_ERROR',
    ];

    /**
     * @deprecated to be removed when dropping support for Symfony 6.1 and older
     */
    protected static $errorNames = self::ERROR_NAMES;

    public ?string $message = 'This value should be equal to {{ compared_value }}.';
}
