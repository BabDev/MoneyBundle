<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use Money\Money;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Provides a base class for the validation of property comparisons.
 *
 * Class is based on \Symfony\Component\Validator\Tests\Constraints\AbstractComparisonValidatorTestCase
 */
abstract class AbstractMoneyComparisonValidatorTestCase extends ConstraintValidatorTestCase
{
    abstract protected function createConstraint(?array $options = null): AbstractMoneyComparison;

    protected function createValueObject(?Money $value): object
    {
        return new class($value) {
            private ?Money $value;

            public function __construct(?Money $value)
            {
                $this->value = $value;
            }

            public function getValue(): ?Money
            {
                return $this->value;
            }
        };
    }

    protected function getErrorCode(): ?string
    {
        return null;
    }

    abstract public function provideValidComparisons(): \Generator;

    abstract public function provideValidComparisonsToPropertyPath(): \Generator;

    abstract public function provideInvalidComparisons(): \Generator;

    abstract public function provideInvalidComparisonToPropertyPath(): array;

    abstract public function provideComparisonsToNullValueAtPropertyPath(): \Generator;

    public function provideInvalidConstraintOptions(): \Generator
    {
        yield 'null configuration' => [null];
        yield 'empty configuration' => [[]];
    }

    /**
     * @dataProvider provideInvalidConstraintOptions
     */
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
        $this->createConstraint(([
            'value' => 'value',
            'propertyPath' => 'propertyPath',
        ]));
    }

    /**
     * @param Money|float|int|string|null $dirtyValue
     * @param Money|float|int|string|null $comparisonValue
     *
     * @dataProvider provideValidComparisons
     */
    public function testValidComparisonToValue($dirtyValue, $comparisonValue): void
    {
        $this->validator->validate($dirtyValue, $this->createConstraint(['value' => $comparisonValue]));

        $this->assertNoViolation();
    }

    /**
     * @param Money|float|int|string|null $comparedValue
     *
     * @dataProvider provideValidComparisonsToPropertyPath
     */
    public function testValidComparisonToPropertyPath($comparedValue): void
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

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage(sprintf('Invalid property path "foo" provided to "%s" constraint', \get_class($constraint)));

        $this->setObject($this->createValueObject(Money::USD(500)));

        $this->validator->validate(500, $constraint);
    }

    /**
     * @param Money|float|int|string|null $dirtyValue
     * @param Money|float|int|string|null $comparedValue
     *
     * @dataProvider provideInvalidComparisons
     */
    public function testInvalidComparisonToValue($dirtyValue, string $dirtyValueAsString, $comparedValue, string $comparedValueString, string $comparedValueType): void
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

    /**
     * @param Money|float|int|string|null $dirtyValue
     *
     * @dataProvider provideComparisonsToNullValueAtPropertyPath
     */
    public function testCompareWithNullValueAtPropertyAt($dirtyValue, string $dirtyValueAsString, bool $isValid): void
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
