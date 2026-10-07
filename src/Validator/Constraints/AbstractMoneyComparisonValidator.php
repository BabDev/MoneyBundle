<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\Number;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Provides a base class for the validation of property comparisons.
 *
 * Class is based on {@see \Symfony\Component\Validator\Constraints\AbstractComparisonValidator}
 */
abstract class AbstractMoneyComparisonValidator extends ConstraintValidator
{
    /**
     * @phpstan-param non-empty-string $defaultCurrency
     */
    public function __construct(
        private readonly FormatterFactoryInterface $formatterFactory,
        private readonly ParserFactoryInterface $parserFactory,
        private readonly string $defaultCurrency,
        private ?PropertyAccessorInterface $propertyAccessor = null
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AbstractMoneyComparison) {
            throw new UnexpectedTypeException($constraint, AbstractMoneyComparison::class);
        }

        if (null === $value) {
            return;
        }

        if ($path = $constraint->propertyPath) {
            if (null === $object = $this->context->getObject()) {
                return;
            }

            try {
                $comparedValue = $this->getPropertyAccessor($constraint)->getValue($object, $path);
            } catch (NoSuchPropertyException $e) {
                throw new InvalidArgumentException(\sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)).$e->getMessage(), 0, $e);
            } catch (UninitializedPropertyException) {
                $comparedValue = null;
            }
        } else {
            $comparedValue = $constraint->value;
        }

        $currency = $this->resolveCurrency($constraint, $value, $comparedValue); // @phpstan-ignore-line argument.type

        $firstValue = $this->ensureMoneyObject($constraint, $value, $currency); // @phpstan-ignore-line argument.type

        // Since we validated $value !== null, we must have a Money object now
        \assert($firstValue instanceof Money);

        $secondValue = $this->ensureMoneyObject($constraint, $comparedValue, $currency); // @phpstan-ignore-line argument.type

        if (null !== $secondValue && $this->requiresSameCurrency() && !$firstValue->isSameCurrency($secondValue)) {
            $this->addViolation($constraint, $constraint->currencyMismatchMessage, AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR, $firstValue, $secondValue, $comparedValue, $path);

            return;
        }

        if (!$this->compareValues($firstValue, $secondValue)) {
            $this->addViolation($constraint, $constraint->message, $this->getErrorCode(), $firstValue, $secondValue, $comparedValue, $path);
        }
    }

    private function addViolation(AbstractMoneyComparison $constraint, string $message, ?string $code, Money $firstValue, ?Money $secondValue, mixed $comparedValue, string|PropertyPathInterface|null $path): void
    {
        $formatter = $this->formatterFactory->createFormatter($constraint->formatterFormat, $constraint->locale, $this->createFactoryOptions($constraint));

        $violationBuilder = $this->context->buildViolation($message)
            ->setParameter('{{ value }}', $formatter->format($firstValue))
            ->setParameter('{{ compared_value }}', null !== $secondValue ? $formatter->format($secondValue) : 'N/A')
            ->setParameter('{{ compared_value_type }}', $this->formatTypeOf($comparedValue))
            ->setCode($code);

        if (null !== $path) {
            $violationBuilder->setParameter('{{ compared_value_path }}', (string) $path);
        }

        $violationBuilder->addViolation();
    }

    /**
     * Resolves the currency used when converting scalar values to a {@see Money} instance.
     *
     * The constraint's currency is preferred, followed by the currency of whichever value is already a {@see Money} instance, and finally the default currency.
     *
     * @param Money|float|int|numeric-string      $value
     * @param Money|float|int|numeric-string|null $comparedValue
     */
    private function resolveCurrency(AbstractMoneyComparison $constraint, Money|float|int|string $value, Money|float|int|string|null $comparedValue): Currency
    {
        if (null !== $constraint->currency && '' !== $constraint->currency) {
            return new Currency($constraint->currency);
        }

        if ($value instanceof Money) {
            return $value->getCurrency();
        }

        if ($comparedValue instanceof Money) {
            return $comparedValue->getCurrency();
        }

        return new Currency($this->defaultCurrency);
    }

    /**
     * @return array{fraction_digits: int<0, max>, grouping_used: bool, style: string}
     */
    private function createFactoryOptions(AbstractMoneyComparison $constraint): array
    {
        return [
            'fraction_digits' => $constraint->fractionDigits,
            'grouping_used' => $constraint->groupingUsed,
            'style' => $constraint->style,
        ];
    }

    /**
     * @param Money|float|int|numeric-string|null $value
     */
    private function ensureMoneyObject(AbstractMoneyComparison $constraint, Money|float|int|string|null $value, Currency $currency): ?Money
    {
        if ($value instanceof Money || null === $value) {
            return $value;
        }

        // First try to parse (assuming formatted input) then fall back to treating as a number
        if (\is_string($value) && str_contains($value, '.')) {
            try {
                return $this->parserFactory->createParser($constraint->parserFormat, $constraint->locale, $this->createFactoryOptions($constraint))->parse($value, $currency);
            } catch (ParserException $exception) {
                throw new InvalidArgumentException(\sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Money::class), 0, $exception);
            }
        }

        try {
            $number = \is_float($value) ? Number::fromFloat($value) : Number::fromNumber($value);
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidArgumentException(\sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Number::class), 0, $exception);
        }

        try {
            return new Money((string) $number, $currency);
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidArgumentException(\sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Money::class), 0, $exception);
        }
    }

    /**
     * @throws LogicException if the property accessor is not available
     */
    private function getPropertyAccessor(AbstractMoneyComparison $constraint): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            if (!class_exists(PropertyAccess::class)) {
                throw new LogicException(\sprintf('The "%s" constraint requires the Symfony PropertyAccess component to use the "propertyPath" option.', get_debug_type($constraint)));
            }

            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    abstract protected function compareValues(Money $value1, ?Money $value2): bool;

    /**
     * Whether the compared values must have the same currency.
     *
     * When true, values with different currencies add a violation using the constraint's currency mismatch message instead of being compared.
     */
    protected function requiresSameCurrency(): bool
    {
        return true;
    }

    protected function getErrorCode(): ?string
    {
        return null;
    }
}
