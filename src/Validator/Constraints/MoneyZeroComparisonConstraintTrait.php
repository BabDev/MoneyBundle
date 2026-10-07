<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Format;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Configures a money comparison constraint to compare a value to zero.
 *
 * Trait is based on {@see \Symfony\Component\Validator\Constraints\ZeroComparisonConstraintTrait}
 *
 * @internal
 *
 * @phpstan-require-extends AbstractMoneyComparison
 */
trait MoneyZeroComparisonConstraintTrait
{
    /**
     * Zero is compared in the currency of the validated value, so the constraint has no "currency" option; as the sign of an amount does not depend on its unit, integer, float, and integer string values are always treated as minor units.
     *
     * @param string[]              $groups          An array of validation groups
     * @param mixed                 $payload         Domain-specific data attached to a constraint
     * @param int<0, max>|null      $fractionDigits  The number of fraction digits used when formatting values; defaults to the number of fraction digits of the value's currency
     * @param bool|null             $groupingUsed    Whether grouping is used when formatting and parsing values
     * @param string|null           $locale          The locale used when formatting and parsing values
     * @param string|null           $style           The number style used when formatting and parsing values with the intl formats, either "currency" or "decimal"; defaults to "decimal" for the "intl_localized_decimal" format and "currency" for the "intl_money" format
     * @param non-empty-string|null $formatterFormat The format used to display values in violation messages
     * @param non-empty-string|null $parserFormat    The format used to parse formatted string values to a Money instance
     */
    #[HasNamedArguments]
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        ?string $formatterFormat = null,
        ?string $parserFormat = null,
        ?int $fractionDigits = null,
        ?bool $groupingUsed = null,
        ?string $locale = null,
        ?string $style = null,
    ) {
        parent::__construct(
            value: 0,
            message: $message,
            groups: $groups,
            payload: $payload,
            formatterFormat: $formatterFormat,
            parserFormat: $parserFormat,
            fractionDigits: $fractionDigits,
            groupingUsed: $groupingUsed,
            locale: $locale,
            style: $style,
            scalarUnit: self::UNIT_MINOR,
        );
    }

    public function validatedBy(): string
    {
        return parent::class.'Validator';
    }
}
