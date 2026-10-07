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
    public const string CURRENCY_MISMATCH_ERROR = '0d6541c7-4a16-43bf-84bd-894c3bd0bfa1';

    public string $message;

    public string $currencyMismatchMessage = 'This value should be in the same currency as {{ compared_value }}.';

    /**
     * @var Money|float|int|numeric-string|null
     */
    public Money|float|int|string|null $value = null;

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
     * @var int<0, max>
     */
    public int $fractionDigits = 2;

    public bool $groupingUsed = true;
    public ?string $locale = null;
    public string $style = 'currency';

    public string|PropertyPathInterface|null $propertyPath = null;

    /**
     * @param Money|float|int|numeric-string|null $value                   The value to compare or a set of options
     * @param string|PropertyPathInterface|null   $propertyPath            An optional property path to read
     * @param string[]                            $groups                  An array of validation groups
     * @param mixed                               $payload                 Domain-specific data attached to a constraint
     * @param non-empty-string|null               $currency                The currency code used when converting scalar values to a Money instance; defaults to the currency of the Money instance being compared to, or the default currency if neither value is a Money instance
     * @param int<0, max>|null                    $fractionDigits          The number of fraction digits used when formatting and parsing values
     * @param bool|null                           $groupingUsed            Whether grouping is used when formatting and parsing values
     * @param string|null                         $locale                  The locale used when formatting and parsing values
     * @param string|null                         $style                   The number style used when formatting and parsing values
     * @param string|null                         $currencyMismatchMessage The message used when the compared values have different currencies
     *
     * @phpstan-param Format::*|null $formatterFormat The format used to display values in violation messages
     * @phpstan-param Format::*|null $parserFormat    The format used to parse scalar values to a Money instance
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

        if (null === $this->value && null === $this->propertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires either the "value" or "propertyPath" option to be set.', static::class));
        }

        if (null !== $this->value && null !== $this->propertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires only one of the "value" or "propertyPath" options to be set, not both.', static::class));
        }

        if (null !== $this->propertyPath && !class_exists(PropertyAccess::class)) {
            throw new LogicException(\sprintf('The "%s" constraint requires the Symfony PropertyAccess component to use the "propertyPath" option.', static::class));
        }
    }
}
