<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Format;
use Money\Money;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * Used for the comparison of Money objects.
 *
 * Class is based on {@see \Symfony\Component\Validator\Constraints\AbstractComparison}
 */
abstract class AbstractMoneyComparison extends Constraint
{
    use MoneyConstraintOptionsTrait;

    public string $message;

    /**
     * @var Money|float|int|numeric-string|null
     */
    public Money|float|int|string|null $value = null;

    public string|PropertyPathInterface|null $propertyPath = null;

    /**
     * @param Money|float|int|numeric-string|null $value                   The value to compare or a set of options
     * @param string|PropertyPathInterface|null   $propertyPath            An optional property path to read
     * @param string[]                            $groups                  An array of validation groups
     * @param mixed                               $payload                 Domain-specific data attached to a constraint
     * @param non-empty-string|null               $currency                The currency code used when converting scalar values to a Money instance; defaults to the currency of the Money instance being compared to, or the default currency if neither value is a Money instance
     * @param int<0, max>|null                    $fractionDigits          The number of fraction digits used when formatting and parsing values; defaults to the number of fraction digits of the value's currency
     * @param bool|null                           $groupingUsed            Whether grouping is used when formatting and parsing values
     * @param string|null                         $locale                  The locale used when formatting and parsing values
     * @param string|null                         $style                   The number style used when formatting and parsing values with the intl formats, either "currency" or "decimal"; defaults to "decimal" for the "intl_localized_decimal" format and "currency" for the "intl_money" format
     * @param string|null                         $currencyMismatchMessage The message used when the compared values have different currencies
     * @param self::UNIT_*|null                   $scalarUnit              The unit of integer, float, and integer string values
     * @param non-empty-string|null               $formatterFormat         The format used to display values in violation messages
     * @param non-empty-string|null               $parserFormat            The format used to parse scalar values to a Money instance
     */
    #[HasNamedArguments]
    public function __construct(
        mixed $value = null,
        $propertyPath = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        ?string $currency = null,
        ?string $formatterFormat = null,
        ?string $parserFormat = null,
        ?int $fractionDigits = null,
        ?bool $groupingUsed = null,
        ?string $locale = null,
        ?string $style = null,
        ?string $currencyMismatchMessage = null,
        ?string $scalarUnit = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->value = $value;
        $this->propertyPath = $propertyPath;
        $this->currency = $currency ?? $this->currency;
        $this->formatterFormat = $formatterFormat ?? $this->formatterFormat;
        $this->parserFormat = $parserFormat ?? $this->parserFormat;
        $this->fractionDigits = $fractionDigits ?? $this->fractionDigits;
        $this->groupingUsed = $groupingUsed ?? $this->groupingUsed;
        $this->locale = $locale ?? $this->locale;
        $this->style = $style ?? $this->style;
        $this->currencyMismatchMessage = $currencyMismatchMessage ?? $this->currencyMismatchMessage;
        $this->scalarUnit = $scalarUnit ?? $this->scalarUnit;

        if (null === $this->value && null === $this->propertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires either the "value" or "propertyPath" option to be set.', static::class));
        }

        if (null !== $this->value && null !== $this->propertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires only one of the "value" or "propertyPath" options to be set, not both.', static::class));
        }

        $this->validateScalarUnit();

        if (null !== $this->propertyPath && !class_exists(PropertyAccess::class)) {
            throw new LogicException(\sprintf('The "%s" constraint requires the Symfony PropertyAccess component to use the "propertyPath" option.', static::class));
        }
    }
}
