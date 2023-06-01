<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Validator\Constraints;

use BabDev\MoneyBundle\Format;
use Money\Money;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * Used for the comparison of Money objects.
 *
 * Class is based on \Symfony\Component\Validator\Constraints\AbstractComparison
 */
abstract class AbstractMoneyComparison extends Constraint
{
    public ?string $message = null;

    /**
     * @var Money|float|int|string|null
     *
     * @phpstan-var Money|float|int|numeric-string|null
     */
    public $value;

    /**
     * @phpstan-var non-empty-string|null
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

    public int $fractionDigits = 2;
    public bool $groupingUsed = true;
    public ?string $locale = null;
    public string $style = 'currency';

    /**
     * @var string|PropertyPathInterface|null
     */
    public $propertyPath;

    /**
     * @param mixed                             $value        The value to compare or a set of options
     * @param string|PropertyPathInterface|null $propertyPath An optional property path to read
     * @param string[]                          $groups       An array of validation groups
     * @param mixed                             $payload      Domain-specific data attached to a constraint
     */
    public function __construct($value = null, $propertyPath = null, ?string $message = null, array $options = [], ?array $groups = null, $payload = null)
    {
        if (\is_array($value)) {
            $options = array_merge($value, $options);
        } elseif (null !== $value) {
            $options['value'] = $value;
        }

        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->propertyPath = $propertyPath ?? $this->propertyPath;

        if (null === $this->value && null === $this->propertyPath) {
            throw new ConstraintDefinitionException(sprintf('The "%s" constraint requires either the "value" or "propertyPath" option to be set.', static::class));
        }

        if (null !== $this->value && null !== $this->propertyPath) {
            throw new ConstraintDefinitionException(sprintf('The "%s" constraint requires only one of the "value" or "propertyPath" options to be set, not both.', static::class));
        }

        if (null !== $this->propertyPath && !class_exists(PropertyAccess::class)) {
            throw new LogicException(sprintf('The "%s" constraint requires the Symfony PropertyAccess component to use the "propertyPath" option.', static::class));
        }
    }

    public function getDefaultOption(): ?string
    {
        return 'value';
    }
}
