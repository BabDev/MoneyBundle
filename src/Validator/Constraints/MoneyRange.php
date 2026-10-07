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
 * Constraint to validate a Money object has a value between a minimum and/or maximum value, inclusive.
 *
 * Class is based on {@see \Symfony\Component\Validator\Constraints\Range}
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MoneyRange extends Constraint
{
    use MoneyConstraintOptionsTrait;

    public const string NOT_IN_RANGE_ERROR = 'eaff3aa5-2669-4f51-bcef-0f839fd7e936';
    public const string TOO_HIGH_ERROR = '769f9ac7-822d-4452-a789-126f4c64eda4';
    public const string TOO_LOW_ERROR = '61424b7f-d5e4-46c3-bf48-fcaf429740ff';

    protected const array ERROR_NAMES = [
        self::NOT_IN_RANGE_ERROR => 'NOT_IN_RANGE_ERROR',
        self::TOO_HIGH_ERROR => 'TOO_HIGH_ERROR',
        self::TOO_LOW_ERROR => 'TOO_LOW_ERROR',
        self::CURRENCY_MISMATCH_ERROR => 'CURRENCY_MISMATCH_ERROR',
    ];

    public string $notInRangeMessage = 'This value should be between {{ min }} and {{ max }}.';
    public string $minMessage = 'This value should be {{ limit }} or more.';
    public string $maxMessage = 'This value should be {{ limit }} or less.';

    /**
     * @var Money|float|int|numeric-string|null
     */
    public Money|float|int|string|null $min = null;

    public string|PropertyPathInterface|null $minPropertyPath = null;

    /**
     * @var Money|float|int|numeric-string|null
     */
    public Money|float|int|string|null $max = null;

    public string|PropertyPathInterface|null $maxPropertyPath = null;

    /**
     * @param Money|float|int|numeric-string|null $min                     The minimum value
     * @param string|PropertyPathInterface|null   $minPropertyPath         A property path to read the minimum value from
     * @param Money|float|int|numeric-string|null $max                     The maximum value
     * @param string|PropertyPathInterface|null   $maxPropertyPath         A property path to read the maximum value from
     * @param string|null                         $notInRangeMessage       The message used when the value is outside of the range and both a minimum and maximum are set
     * @param string|null                         $minMessage              The message used when the value is less than the minimum and no maximum is set
     * @param string|null                         $maxMessage              The message used when the value is greater than the maximum and no minimum is set
     * @param string[]                            $groups                  An array of validation groups
     * @param mixed                               $payload                 Domain-specific data attached to a constraint
     * @param non-empty-string|null               $currency                The currency code used when converting scalar values to a Money instance; defaults to the currency of the first Money instance of the value, minimum, and maximum, or the default currency if none are Money instances
     * @param int<0, max>|null                    $fractionDigits          The number of fraction digits used when formatting values; defaults to the number of fraction digits of the value's currency
     * @param bool|null                           $groupingUsed            Whether grouping is used when formatting and parsing values
     * @param string|null                         $locale                  The locale used when formatting and parsing values
     * @param string|null                         $style                   The number style used when formatting and parsing values
     * @param string|null                         $currencyMismatchMessage The message used when the value and a limit have different currencies
     * @param self::UNIT_*|null                   $scalarUnit              The unit of integer, float, and integer string values
     *
     * @phpstan-param Format::*|null $formatterFormat The format used to display values in violation messages
     * @phpstan-param Format::*|null $parserFormat    The format used to parse formatted string values to a Money instance
     */
    #[HasNamedArguments]
    public function __construct(
        mixed $min = null,
        string|PropertyPathInterface|null $minPropertyPath = null,
        mixed $max = null,
        string|PropertyPathInterface|null $maxPropertyPath = null,
        ?string $notInRangeMessage = null,
        ?string $minMessage = null,
        ?string $maxMessage = null,
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

        $this->min = $min;
        $this->minPropertyPath = $minPropertyPath;
        $this->max = $max;
        $this->maxPropertyPath = $maxPropertyPath;
        $this->notInRangeMessage = $notInRangeMessage ?? $this->notInRangeMessage;
        $this->minMessage = $minMessage ?? $this->minMessage;
        $this->maxMessage = $maxMessage ?? $this->maxMessage;
        $this->currency = $currency ?? $this->currency;
        $this->formatterFormat = $formatterFormat ?? $this->formatterFormat;
        $this->parserFormat = $parserFormat ?? $this->parserFormat;
        $this->fractionDigits = $fractionDigits ?? $this->fractionDigits;
        $this->groupingUsed = $groupingUsed ?? $this->groupingUsed;
        $this->locale = $locale ?? $this->locale;
        $this->style = $style ?? $this->style;
        $this->currencyMismatchMessage = $currencyMismatchMessage ?? $this->currencyMismatchMessage;
        $this->scalarUnit = $scalarUnit ?? $this->scalarUnit;

        if (null === $this->min && null === $this->minPropertyPath && null === $this->max && null === $this->maxPropertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires at least one of the "min", "minPropertyPath", "max", or "maxPropertyPath" options to be set.', static::class));
        }

        if (null !== $this->min && null !== $this->minPropertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires only one of the "min" or "minPropertyPath" options to be set, not both.', static::class));
        }

        if (null !== $this->max && null !== $this->maxPropertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires only one of the "max" or "maxPropertyPath" options to be set, not both.', static::class));
        }

        $hasMin = null !== $this->min || null !== $this->minPropertyPath;
        $hasMax = null !== $this->max || null !== $this->maxPropertyPath;

        if ($hasMin && $hasMax && (null !== $minMessage || null !== $maxMessage)) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint can not use the "minMessage" and "maxMessage" options when both a minimum and maximum are set, use the "notInRangeMessage" option instead.', static::class));
        }

        $this->validateScalarUnit();

        if ((null !== $this->minPropertyPath || null !== $this->maxPropertyPath) && !class_exists(PropertyAccess::class)) {
            throw new LogicException(\sprintf('The "%s" constraint requires the Symfony PropertyAccess component to use the "minPropertyPath" or "maxPropertyPath" options.', static::class));
        }
    }
}
