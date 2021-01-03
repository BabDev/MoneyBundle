<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value less than or equal to the compared value.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyLessThanOrEqual extends AbstractMoneyComparison
{
    public const TOO_HIGH_ERROR = 'eca16a86-47e0-4a2f-bc44-f9b3d58561e5';

    /**
     * Maps error codes to the names of their constants.
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::TOO_HIGH_ERROR => 'TOO_HIGH_ERROR',
    ];

    public ?string $message = 'This value should be less than or equal to {{ compared_value }}.';
}
