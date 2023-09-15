<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value less than or equal to the compared value.
 *
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyLessThanOrEqual extends AbstractMoneyComparison
{
    public const TOO_HIGH_ERROR = 'eca16a86-47e0-4a2f-bc44-f9b3d58561e5';

    protected const ERROR_NAMES = [
        self::TOO_HIGH_ERROR => 'TOO_HIGH_ERROR',
    ];

    /**
     * @deprecated to be removed when dropping support for Symfony 6.1 and older
     */
    protected static $errorNames = self::ERROR_NAMES;

    public ?string $message = 'This value should be less than or equal to {{ compared_value }}.';
}
