<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use Money\Currencies;
use Money\Currencies\ISOCurrencies;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator ensuring a Money object has a value between a minimum and/or maximum value, inclusive.
 *
 * Class is based on {@see \Symfony\Component\Validator\Constraints\RangeValidator}
 */
final class MoneyRangeValidator extends ConstraintValidator
{
    use ConvertsMoneyValues;

    /**
     * @phpstan-param non-empty-string $defaultCurrency
     */
    public function __construct(
        private readonly FormatterFactoryInterface $formatterFactory,
        private readonly ParserFactoryInterface $parserFactory,
        private readonly string $defaultCurrency,
        private ?PropertyAccessorInterface $propertyAccessor = null,
        private readonly Currencies $currencies = new ISOCurrencies(),
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MoneyRange) {
            throw new UnexpectedTypeException($constraint, MoneyRange::class);
        }

        if (null === $value) {
            return;
        }

        $value = $this->ensureConvertibleValue($value);
        $min = $this->ensureConvertibleComparedValue($constraint, $this->getLimit($constraint, $constraint->min, $constraint->minPropertyPath), $constraint->minPropertyPath);
        $max = $this->ensureConvertibleComparedValue($constraint, $this->getLimit($constraint, $constraint->max, $constraint->maxPropertyPath), $constraint->maxPropertyPath);

        $currency = $this->resolveCurrency($constraint, $value, $min, $max);

        // The limits are converted first so an invalid constraint definition always throws, even when the validated value is invalid
        $minValue = $this->ensureMoneyObject($constraint, $min, $currency);
        $maxValue = $this->ensureMoneyObject($constraint, $max, $currency);

        if (null === $moneyValue = $this->convertValidatedValue($constraint, $value, $currency)) {
            return;
        }

        $formatter = $this->formatterFactory->createFormatter($constraint->formatterFormat, $constraint->locale, $this->createFactoryOptions($constraint));

        foreach ([$minValue, $maxValue] as $limit) {
            if (null !== $limit && !$moneyValue->isSameCurrency($limit)) {
                $this->context->buildViolation($constraint->currencyMismatchMessage)
                    ->setParameter('{{ value }}', $formatter->format($moneyValue))
                    ->setParameter('{{ compared_value }}', $formatter->format($limit))
                    ->setCode(MoneyRange::CURRENCY_MISMATCH_ERROR)
                    ->addViolation();

                return;
            }
        }

        if (null !== $minValue && null !== $maxValue) {
            if (!$moneyValue->lessThan($minValue) && !$moneyValue->greaterThan($maxValue)) {
                return;
            }

            $violationBuilder = $this->context->buildViolation($constraint->notInRangeMessage)
                ->setParameter('{{ min }}', $formatter->format($minValue))
                ->setParameter('{{ max }}', $formatter->format($maxValue))
                ->setCode(MoneyRange::NOT_IN_RANGE_ERROR);
        } elseif (null !== $maxValue && $moneyValue->greaterThan($maxValue)) {
            $violationBuilder = $this->context->buildViolation($constraint->maxMessage)
                ->setParameter('{{ limit }}', $formatter->format($maxValue))
                ->setCode(MoneyRange::TOO_HIGH_ERROR);
        } elseif (null !== $minValue && $moneyValue->lessThan($minValue)) {
            $violationBuilder = $this->context->buildViolation($constraint->minMessage)
                ->setParameter('{{ limit }}', $formatter->format($minValue))
                ->setCode(MoneyRange::TOO_LOW_ERROR);
        } else {
            return;
        }

        $violationBuilder->setParameter('{{ value }}', $formatter->format($moneyValue));

        if (null !== $constraint->minPropertyPath) {
            $violationBuilder->setParameter('{{ min_limit_path }}', (string) $constraint->minPropertyPath);
        }

        if (null !== $constraint->maxPropertyPath) {
            $violationBuilder->setParameter('{{ max_limit_path }}', (string) $constraint->maxPropertyPath);
        }

        $violationBuilder->addViolation();
    }

    /**
     * Reads a limit from the validated object when a property path is set, or a null limit if there is no object or the property is not initialized.
     */
    private function getLimit(MoneyRange $constraint, mixed $limit, string|PropertyPathInterface|null $path): mixed
    {
        if (null === $path) {
            return $limit;
        }

        if (null === $object = $this->context->getObject()) {
            return null;
        }

        try {
            return $this->getPropertyAccessor($constraint)->getValue($object, $path);
        } catch (NoSuchPropertyException $e) {
            throw new InvalidArgumentException(\sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)).$e->getMessage(), 0, $e);
        } catch (UninitializedPropertyException) {
            return null;
        }
    }
}
