<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Format;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Options for converting values to {@see \Money\Money} instances and formatting them in violation messages.
 *
 * @internal
 */
trait MoneyConstraintOptionsTrait
{
    public const string CURRENCY_MISMATCH_ERROR = '0d6541c7-4a16-43bf-84bd-894c3bd0bfa1';

    /**
     * Integer, float, and integer string values represent an amount in the currency's minor unit (i.e. 500 is $5.00).
     */
    public const string UNIT_MINOR = 'minor';

    /**
     * Integer, float, and integer string values represent an amount in the currency's major unit (i.e. 500 is $500.00).
     */
    public const string UNIT_MAJOR = 'major';

    public string $currencyMismatchMessage = 'This value should be in the same currency as {{ compared_value }}.';

    /**
     * @var non-empty-string|null
     */
    public ?string $currency = null;

    /**
     * @phpstan-var Format::*
     */
    public string $formatterFormat = Format::INTL_MONEY;

    /**
     * @phpstan-var Format::*
     */
    public string $parserFormat = Format::DECIMAL;

    /**
     * The number of fraction digits used when formatting values, or null to use the number of fraction digits of the value's currency.
     *
     * @var int<0, max>|null
     */
    public ?int $fractionDigits = null;

    public bool $groupingUsed = true;
    public ?string $locale = null;

    /**
     * The number style used by the intl formatters and parsers, or null to use the default style of the format.
     */
    public ?string $style = null;

    /**
     * The unit of integer, float, and integer string values; formatted strings are always parsed with the parser format.
     *
     * When not set, values are treated as minor units; this default is deprecated and will change to major units in 4.0.
     *
     * @var self::UNIT_*|null
     */
    public ?string $scalarUnit = null;

    /**
     * @throws ConstraintDefinitionException if the "scalarUnit" option is invalid
     */
    private function validateScalarUnit(): void
    {
        if (null !== $this->scalarUnit && !\in_array($this->scalarUnit, [self::UNIT_MINOR, self::UNIT_MAJOR], true)) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires the "scalarUnit" option to be one of "%s" or "%s", "%s" given.', static::class, self::UNIT_MINOR, self::UNIT_MAJOR, $this->scalarUnit));
        }
    }
}
