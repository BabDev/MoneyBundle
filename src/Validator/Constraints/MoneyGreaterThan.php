<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

/**
 * Constraint to validate a Money object has a value greater than the compared value.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyGreaterThan extends AbstractMoneyComparison
{
    public const TOO_LOW_ERROR = '11c8f681-95b7-47ee-ad9a-d28dfdbb8443';

    /**
     * Maps error codes to the names of their constants.
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::TOO_LOW_ERROR => 'TOO_LOW_ERROR',
    ];

    public ?string $message = 'This value should be greater than {{ compared_value }}.';
}
