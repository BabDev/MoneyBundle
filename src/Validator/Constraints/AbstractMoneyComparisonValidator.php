<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\Number;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Provides a base class for the validation of property comparisons.
 *
 * Class is based on \Symfony\Component\Validator\Constraints\AbstractComparisonValidator
 */
abstract class AbstractMoneyComparisonValidator extends ConstraintValidator
{
    private FormatterFactoryInterface $formatterFactory;
    private ParserFactoryInterface $parserFactory;

    /**
     * @phpstan-var non-empty-string
     */
    private string $defaultCurrency;

    private ?PropertyAccessorInterface $propertyAccessor;

    /**
     * @phpstan-param non-empty-string $defaultCurrency
     */
    public function __construct(FormatterFactoryInterface $formatterFactory, ParserFactoryInterface $parserFactory, string $defaultCurrency, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->formatterFactory = $formatterFactory;
        $this->parserFactory = $parserFactory;
        $this->defaultCurrency = $defaultCurrency;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
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
                $comparedValue = $this->getPropertyAccessor()->getValue($object, $path);
            } catch (NoSuchPropertyException $e) {
                throw new InvalidArgumentException(sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)).$e->getMessage(), 0, $e);
            }
        } else {
            $comparedValue = $constraint->value;
        }

        /** @var Money $firstValue */
        $firstValue = $this->ensureMoneyObject($constraint, $value);
        $secondValue = $this->ensureMoneyObject($constraint, $comparedValue);

        if (!$this->compareValues($firstValue, $secondValue)) {
            $violationBuilder = $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatterFactory->createFormatter($constraint->formatterFormat, $constraint->locale, $this->createFactoryOptions($constraint))->format($firstValue))
                ->setParameter('{{ compared_value }}', null !== $secondValue ? $this->formatterFactory->createFormatter($constraint->formatterFormat, $constraint->locale, $this->createFactoryOptions($constraint))->format($secondValue) : 'N/A')
                ->setParameter('{{ compared_value_type }}', $this->formatTypeOf($comparedValue))
                ->setCode($this->getErrorCode());

            if (null !== $path) {
                $violationBuilder->setParameter('{{ compared_value_path }}', (string) $path);
            }

            $violationBuilder->addViolation();
        }
    }

    private function createFactoryOptions(AbstractMoneyComparison $constraint): array
    {
        return [
            'fraction_digits' => $constraint->fractionDigits,
            'grouping_used' => $constraint->groupingUsed,
            'style' => $constraint->style,
        ];
    }

    /**
     * @param Money|float|int|string|null $value
     *
     * @phpstan-param Money|float|int|numeric-string|null $value
     */
    private function ensureMoneyObject(AbstractMoneyComparison $constraint, $value): ?Money
    {
        if ($value instanceof Money || null === $value) {
            return $value;
        }

        if (\is_object($value) || \is_array($value)) {
            throw new InvalidArgumentException(sprintf('Could not convert value of type "%s" to a "%s" instance for comparison.', get_debug_type($value), Money::class));
        }

        // First try to parse (assuming formatted input) then fall back to treating as a number
        if (\is_string($value) && str_contains($value, '.')) {
            try {
                return $this->parserFactory->createParser($constraint->parserFormat, $constraint->locale, $this->createFactoryOptions($constraint))->parse($value, new Currency($constraint->currency ?: $this->defaultCurrency));
            } catch (ParserException $exception) {
                throw new InvalidArgumentException(sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Money::class));
            }
        }

        try {
            if (\is_float($value)) {
                $number = Number::fromFloat($value);
            } else {
                $number = Number::fromNumber($value);
            }
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidArgumentException(sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Number::class));
        }

        try {
            return new Money((string) $number, new Currency($constraint->currency ?: $this->defaultCurrency));
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidArgumentException(sprintf('Could not convert value "%s" to a "%s" instance for comparison.', $value, Money::class));
        }
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    abstract protected function compareValues(Money $value1, ?Money $value2): bool;

    protected function getErrorCode(): ?string
    {
        return null;
    }
}
