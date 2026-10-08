<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Format;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\Number;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Converts values to {@see Money} instances for the money validators.
 *
 * The using class must provide the $formatterFactory, $parserFactory, $defaultCurrency, $propertyAccessor, and $currencies properties.
 *
 * @internal
 *
 * @phpstan-require-extends ConstraintValidator
 */
trait ConvertsMoneyValues
{
    /**
     * Resolves the currency used when converting scalar values to a {@see Money} instance.
     *
     * The constraint's currency is preferred, followed by the currency of the first value which is already a {@see Money} instance, and finally the default currency.
     */
    private function resolveCurrency(AbstractMoneyComparison|MoneyRange $constraint, Money|float|int|string|null ...$values): Currency
    {
        if (null !== $constraint->currency && '' !== $constraint->currency) {
            return new Currency($constraint->currency);
        }

        foreach ($values as $value) {
            if ($value instanceof Money) {
                return $value->getCurrency();
            }
        }

        return new Currency($this->defaultCurrency);
    }

    /**
     * @return array{fraction_digits: int<0, max>|null, grouping_used: bool, style: string|null}
     */
    private function createFactoryOptions(AbstractMoneyComparison|MoneyRange $constraint): array
    {
        return [
            'fraction_digits' => $constraint->fractionDigits,
            'grouping_used' => $constraint->groupingUsed,
            'style' => $constraint->style,
        ];
    }

    /**
     * @param Money|float|int|string|null $value Formatted strings are parsed with the constraint's parser format
     *
     * @return ($value is null ? null : Money)
     *
     * @throws InvalidArgumentException if the value cannot be converted
     */
    private function ensureMoneyObject(AbstractMoneyComparison|MoneyRange $constraint, Money|float|int|string|null $value, Currency $currency): ?Money
    {
        if ($value instanceof Money || null === $value) {
            return $value;
        }

        // Formatted strings are always parsed with the configured parser
        if (\is_string($value) && 1 !== preg_match('/^-?\d+$/', $value)) {
            return $this->parse($constraint, $constraint->parserFormat, $value, $currency);
        }

        $scalarUnit = $constraint->scalarUnit;

        if (null === $scalarUnit) {
            if (!$this->isZero($value)) {
                trigger_deprecation('babdev/money-bundle', '3.2', 'Comparing the scalar value "%s" with the "%s" constraint without setting the "scalarUnit" option is deprecated, the value is treated as an amount in minor units. In 4.0, the default will change to major units; set the option to "%s" to keep the current behavior or "%s" to opt in to the new behavior.', $value, get_debug_type($constraint), AbstractMoneyComparison::UNIT_MINOR, AbstractMoneyComparison::UNIT_MAJOR);
            }

            $scalarUnit = AbstractMoneyComparison::UNIT_MINOR;
        }

        try {
            $number = \is_float($value) ? Number::fromFloat($value) : Number::fromNumber($value);
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidArgumentException(\sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Number::class), 0, $exception);
        }

        if (AbstractMoneyComparison::UNIT_MAJOR === $scalarUnit) {
            return $this->parse($constraint, Format::DECIMAL, (string) $number, $currency);
        }

        try {
            return new Money((string) $number, $currency);
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidArgumentException(\sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Money::class), 0, $exception);
        }
    }

    /**
     * Converts the validated value to a {@see Money} instance, adding a violation using the constraint's invalid message if it cannot be converted.
     *
     * @return Money|null The converted value, or null if a violation was added
     */
    private function convertValidatedValue(AbstractMoneyComparison|MoneyRange $constraint, Money|float|int|string $value, Currency $currency): ?Money
    {
        try {
            $money = $this->ensureMoneyObject($constraint, $value, $currency);
        } catch (InvalidArgumentException) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode($constraint::INVALID_VALUE_ERROR)
                ->addViolation();

            return null;
        }

        if ($constraint->rejectExcessFractionDigits && !$value instanceof Money) {
            $limit = $this->currencies->subunitFor($currency);

            if ($this->wasRounded($constraint, $value, $money, $limit)) {
                $this->context->buildViolation($constraint->excessFractionDigitsMessage)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setParameter('{{ limit }}', (string) $limit)
                    ->setPlural($limit)
                    ->setCode($constraint::TOO_MANY_FRACTION_DIGITS_ERROR)
                    ->addViolation();

                return null;
            }
        }

        return $money;
    }

    /**
     * Checks whether parsing a scalar value rounded it to the fraction digits of its currency.
     */
    private function wasRounded(AbstractMoneyComparison|MoneyRange $constraint, float|int|string $value, Money $money, int $fractionDigits): bool
    {
        if (\is_string($value) && 1 !== preg_match('/^-?\d+$/', $value)) {
            $format = $constraint->parserFormat;
        } elseif (\is_float($value) && AbstractMoneyComparison::UNIT_MAJOR === $constraint->scalarUnit) {
            $format = Format::DECIMAL;
            $value = (string) Number::fromFloat($value);
        } else {
            return false;
        }

        $formatted = $this->formatterFactory->createFormatter($format, $constraint->locale, ['fraction_digits' => $fractionDigits] + $this->createFactoryOptions($constraint))->format($money);

        return $this->extractSignificantDigits($value) !== $this->extractSignificantDigits($formatted);
    }

    /**
     * Extracts the digits of a value, as ASCII digits and without leading and trailing zeros, ignoring any other characters such as signs, separators, and currency symbols.
     */
    private function extractSignificantDigits(string $value): string
    {
        if (false === preg_match_all('/\p{Nd}/u', $value, $matches)) {
            return '';
        }

        // Localized formats may use digits from other scripts
        $digits = array_map(static fn (string $digit): string => ctype_digit($digit) ? $digit : (string) \IntlChar::charDigitValue($digit), $matches[0]);

        return trim(implode('', $digits), '0');
    }

    /**
     * @param non-empty-string $format
     */
    private function parse(AbstractMoneyComparison|MoneyRange $constraint, string $format, string $value, Currency $currency): Money
    {
        try {
            return $this->parserFactory->createParser($format, $constraint->locale, $this->createFactoryOptions($constraint))->parse($value, $currency);
        } catch (ParserException $exception) {
            throw new InvalidArgumentException(\sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Money::class), 0, $exception);
        }
    }

    private function isZero(float|int|string $value): bool
    {
        return \is_string($value) ? 1 !== preg_match('/[1-9]/', $value) : 0.0 === (float) $value;
    }

    /**
     * @throws LogicException if the property accessor is not available
     */
    private function getPropertyAccessor(AbstractMoneyComparison|MoneyRange $constraint): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            if (!class_exists(PropertyAccess::class)) {
                throw new LogicException(\sprintf('The "%s" constraint requires the Symfony PropertyAccess component to use the "propertyPath" option.', get_debug_type($constraint)));
            }

            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @throws UnexpectedValueException if the validated value cannot be converted to a {@see Money} instance, which the validator reports as a violation
     */
    private function ensureConvertibleValue(mixed $value): Money|float|int|string
    {
        if ($value instanceof Money || \is_int($value) || \is_float($value) || \is_string($value)) {
            return $value;
        }

        throw new UnexpectedValueException($value, Money::class.'|int|float|string');
    }

    /**
     * @throws InvalidArgumentException if the value read from a property path cannot be converted to a {@see Money} instance
     */
    private function ensureConvertibleComparedValue(AbstractMoneyComparison|MoneyRange $constraint, mixed $value, string|PropertyPathInterface|null $path): Money|float|int|string|null
    {
        if (null === $value || $value instanceof Money || \is_int($value) || \is_float($value) || \is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(\sprintf('The value of the "%s" property path provided to the "%s" constraint must be a "%s" instance, an integer, a float, or a string, "%s" given.', (string) $path, get_debug_type($constraint), Money::class, get_debug_type($value)));
    }
}
