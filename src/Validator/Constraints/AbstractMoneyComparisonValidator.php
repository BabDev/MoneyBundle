<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Provides a base class for the validation of property comparisons.
 *
 * Class is based on {@see \Symfony\Component\Validator\Constraints\AbstractComparisonValidator}
 */
abstract class AbstractMoneyComparisonValidator extends ConstraintValidator
{
    use ConvertsMoneyValues;

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

        $value = $this->ensureConvertibleValue($value);

        if ($path = $constraint->propertyPath) {
            if (null === $object = $this->context->getObject()) {
                return;
            }

            try {
                $comparedValue = $this->ensureConvertibleComparedValue($constraint, $this->getPropertyAccessor($constraint)->getValue($object, $path), $path);
            } catch (NoSuchPropertyException $e) {
                throw new InvalidArgumentException(\sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)).$e->getMessage(), 0, $e);
            } catch (UninitializedPropertyException) {
                $comparedValue = null;
            }
        } else {
            $comparedValue = $constraint->value;
        }

        $currency = $this->resolveCurrency($constraint, $value, $comparedValue);

        // The compared value is converted first so an invalid constraint definition always throws, even when the validated value is invalid
        $secondValue = $this->ensureMoneyObject($constraint, $comparedValue, $currency);

        if (null === $firstValue = $this->convertValidatedValue($constraint, $value, $currency)) {
            return;
        }

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
