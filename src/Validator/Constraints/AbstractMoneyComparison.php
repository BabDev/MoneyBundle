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
    public ?string $message = null;

    /**
     * @phpstan-var Money|float|int|numeric-string|null
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
     * @param mixed                             $value        The value to compare or a set of options
     * @param string|PropertyPathInterface|null $propertyPath An optional property path to read
     * @param array<string, mixed>|null         $options      A set of options
     * @param string[]                          $groups       An array of validation groups
     * @param mixed                             $payload      Domain-specific data attached to a constraint
     */
    #[HasNamedArguments]
    public function __construct(mixed $value = null, $propertyPath = null, ?string $message = null, ?array $options = null, ?array $groups = null, mixed $payload = null)
    {
        if (\is_array($value)) {
            trigger_deprecation('babdev/money-bundle', '2.1', 'Passing an array of options to configure the "%s" constraint is deprecated, use named arguments instead.', static::class);

            $options = array_merge($value, $options ?? []);
        } elseif (null !== $value) {
            if (\is_array($options)) {
                trigger_deprecation('babdev/money-bundle', '2.1', 'Passing an array of options to configure the "%s" constraint is deprecated, use named arguments instead.', static::class);
            } else {
                $options = [];
            }

            $options['value'] = $value;
        }

        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->propertyPath = $propertyPath ?? $this->propertyPath;

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

    public function getDefaultOption(): ?string
    {
        return 'value';
    }
}
