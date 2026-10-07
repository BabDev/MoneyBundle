<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Format;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\Number;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * Converts values to {@see Money} instances for the money validators.
 *
 * The using class must provide the $parserFactory, $defaultCurrency, and $propertyAccessor properties.
 *
 * @internal
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
     * @phpstan-param Format::* $format
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
}
