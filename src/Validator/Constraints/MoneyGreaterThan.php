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

    protected const ERROR_NAMES = [
        self::TOO_LOW_ERROR => 'TOO_LOW_ERROR',
    ];

    /**
     * @var array<string, string>
     *
     * @deprecated to be removed when dropping support for Symfony 6.1 and older
     */
    protected static $errorNames = self::ERROR_NAMES;

    public ?string $message = 'This value should be greater than {{ compared_value }}.';
}
