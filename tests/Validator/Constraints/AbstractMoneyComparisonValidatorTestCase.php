<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparisonValidator;
use Money\Money;
use Money\Number;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Provides a base class for the validation of property comparisons.
 *
 * Class is based on \Symfony\Component\Validator\Tests\Constraints\AbstractComparisonValidatorTestCase
 *
 * @extends ConstraintValidatorTestCase<AbstractMoneyComparisonValidator>
 */
abstract class AbstractMoneyComparisonValidatorTestCase extends ConstraintValidatorTestCase
{
    /**
     * @param array<string, mixed>|null $options An array of named arguments for the constraint or null for no arguments
     */
    abstract protected function createConstraint(?array $options = null): AbstractMoneyComparison;

    protected function createValueObject(?Money $value): object
    {
        return new class($value) {
            public function __construct(private readonly ?Money $value) {}

            public function getValue(): ?Money
            {
                return $this->value;
            }
        };
    }

    protected function getErrorCode(): string
    {
        return '';
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null, Money|float|int|string|null}>
     */
    abstract public static function provideValidComparisons(): \Generator;

    /**
     * @return \Generator<string, array{Money|float|int|string|null}>
     */
    abstract public static function provideValidComparisonsToPropertyPath(): \Generator;

    /**
     * @return \Generator<string, array{Money|float|int|string|null, string, Money|float|int|string|null, string, string|class-string<Money>}>
     */
    abstract public static function provideInvalidComparisons(): \Generator;

    /**
     * @return array{Money, non-empty-string, Money, non-empty-string, class-string<Money>}
     */
    abstract public function provideInvalidComparisonToPropertyPath(): array;

    /**
     * @return \Generator<string, array{Money|float|int|string|null, string, bool}>
     */
    abstract public static function provideComparisonsToNullValueAtPropertyPath(): \Generator;

    /**
     * @return \Generator<string, array{array<string, mixed>|null}>
     */
    public static function provideInvalidConstraintOptions(): \Generator
    {
        yield 'null configuration' => [null];
        yield 'empty configuration' => [[]];
    }

    /**
     * @param array<string, mixed>|null $options
     */
    #[DataProvider('provideInvalidConstraintOptions')]
    public function testThrowsConstraintExceptionIfNoValueOrPropertyPath(?array $options): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('requires either the "value" or "propertyPath" option to be set.');
        $this->createConstraint($options);
    }

    public function testThrowsConstraintExceptionIfBothValueAndPropertyPath(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('requires only one of the "value" or "propertyPath" options to be set, not both.');
        $this->createConstraint([
            'value' => 'value',
            'propertyPath' => 'propertyPath',
        ]);
    }

    #[DataProvider('provideValidComparisons')]
    public function testValidComparisonToValue(Money|float|int|string|null $dirtyValue, Money|float|int|string|null $comparisonValue): void
    {
        $this->validator->validate($dirtyValue, $this->createConstraint(['value' => $comparisonValue]));

        $this->assertNoViolation();
    }

    #[DataProvider('provideValidComparisonsToPropertyPath')]
    public function testValidComparisonToPropertyPath(Money|float|int|string|null $comparedValue): void
    {
        $this->setObject($this->createValueObject(Money::USD(500)));

        $this->validator->validate($comparedValue, $this->createConstraint(['propertyPath' => 'value']));

        $this->assertNoViolation();
    }

    public function testNoViolationOnNullObjectWithPropertyPath(): void
    {
        $this->setObject(null);

        $this->validator->validate(Money::USD(500), $this->createConstraint(['propertyPath' => 'propertyPath']));

        $this->assertNoViolation();
    }

    public function testInvalidValuePath(): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'foo']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid property path "foo" provided to "%s" constraint', $constraint::class));

        $this->setObject($this->createValueObject(Money::USD(500)));

        $this->validator->validate(500, $constraint);
    }

    public function testInvalidValueAsBadlyFormattedString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Could not convert value "." to a "%s" instance for comparison.', Money::class));

        $this->validator->validate(500, $this->createConstraint(['value' => '.']));
    }

    public function testInvalidValueAsNonNumericString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Could not convert value "INVALID" to a "%s" instance for comparison.', Number::class));

        $this->validator->validate(500, $this->createConstraint(['value' => 'INVALID']));
    }

    public function testInvalidValueAsBadlyFormattedFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Could not convert value "500.4925" to a "%s" instance for comparison.', Money::class));

        $this->validator->validate(500, $this->createConstraint(['value' => 500.4925]));
    }

    #[DataProvider('provideInvalidComparisons')]
    public function testInvalidComparisonToValue(Money|float|int|string|null $dirtyValue, string $dirtyValueAsString, Money|float|int|string|null $comparedValue, string $comparedValueString, string $comparedValueType): void
    {
        $constraint = $this->createConstraint(['value' => $comparedValue]);
        $constraint->message = 'Constraint Message';

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', $dirtyValueAsString)
            ->setParameter('{{ compared_value }}', $comparedValueString)
            ->setParameter('{{ compared_value_type }}', $comparedValueType)
            ->setCode($this->getErrorCode())
            ->assertRaised();
    }

    public function testInvalidComparisonToPropertyPathAddsPathAsParameter(): void
    {
        [$dirtyValue, $dirtyValueAsString, $comparedValue, $comparedValueString, $comparedValueType] = $this->provideInvalidComparisonToPropertyPath();

        $constraint = $this->createConstraint(['propertyPath' => 'value']);
        $constraint->message = 'Constraint Message';

        $this->setObject($this->createValueObject($comparedValue));

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', $dirtyValueAsString)
            ->setParameter('{{ compared_value }}', $comparedValueString)
            ->setParameter('{{ compared_value_path }}', 'value')
            ->setParameter('{{ compared_value_type }}', $comparedValueType)
            ->setCode($this->getErrorCode())
            ->assertRaised();
    }

    #[DataProvider('provideComparisonsToNullValueAtPropertyPath')]
    public function testCompareWithNullValueAtPropertyAt(Money|float|int|string|null $dirtyValue, string $dirtyValueAsString, bool $isValid): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'value']);
        $constraint->message = 'Constraint Message';

        $this->setObject($this->createValueObject(null));

        $this->validator->validate($dirtyValue, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('Constraint Message')
                ->setParameter('{{ value }}', $dirtyValueAsString)
                ->setParameter('{{ compared_value }}', 'null')
                ->setParameter('{{ compared_value_type }}', 'null')
                ->setParameter('{{ compared_value_path }}', 'value')
                ->setCode($this->getErrorCode())
                ->assertRaised();
        }
    }
}
