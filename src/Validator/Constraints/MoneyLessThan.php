<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value less than the compared value.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyLessThan extends AbstractMoneyComparison
{
    public const TOO_HIGH_ERROR = 'dbeda9a5-ab67-4c21-a8b9-db816ec0c912';

    protected const ERROR_NAMES = [
        self::TOO_HIGH_ERROR => 'TOO_HIGH_ERROR',
    ];

    /**
     * @var array<string, string>
     *
     * @deprecated to be removed when dropping support for Symfony 6.1 and older
     */
    protected static $errorNames = self::ERROR_NAMES;

    public ?string $message = 'This value should be less than {{ compared_value }}.';
}
