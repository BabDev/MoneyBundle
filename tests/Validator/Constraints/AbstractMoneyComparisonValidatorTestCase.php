<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparisonValidator;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
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
        return new readonly class($value) {
            public function __construct(public private(set) ?Money $value) {}
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
        $this->validator->validate($dirtyValue, $this->createConstraint(['value' => $comparisonValue, 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]));

        $this->assertNoViolation();
    }

    #[DataProvider('provideValidComparisonsToPropertyPath')]
    public function testValidComparisonToPropertyPath(Money|float|int|string|null $comparedValue): void
    {
        $this->setObject($this->createValueObject(Money::USD(500)));

        $this->validator->validate($comparedValue, $this->createConstraint(['propertyPath' => 'value', 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]));

        $this->assertNoViolation();
    }

    public function testUnsupportedValuesAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(['amount' => 500], $this->createConstraint(['value' => Money::USD(500)]));
    }

    public function testUnsupportedValuesFromAPropertyPathAreRejected(): void
    {
        $this->setObject(new class {
            /**
             * @var list<int>
             */
            public array $value = [500];
        });

        $constraint = $this->createConstraint(['propertyPath' => 'value']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The value of the "value" property path provided to the "%s" constraint must be a "%s" instance, an integer, a float, or a string, "array" given.', $constraint::class, Money::class));

        $this->validator->validate(Money::USD(500), $constraint);
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

        $this->validator->validate(500, $this->createConstraint(['value' => '.', 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]));
    }

    public function testInvalidValueAsNonNumericString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Could not convert value "INVALID" to a "%s" instance for comparison.', Money::class));

        $this->validator->validate(500, $this->createConstraint(['value' => 'INVALID', 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]));
    }

    public function testInvalidValueAsBadlyFormattedFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Could not convert value "500.4925" to a "%s" instance for comparison.', Money::class));

        $this->validator->validate(500, $this->createConstraint(['value' => 500.4925, 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]));
    }

    #[DataProvider('provideInvalidComparisons')]
    public function testInvalidComparisonToValue(Money|float|int|string|null $dirtyValue, string $dirtyValueAsString, Money|float|int|string|null $comparedValue, string $comparedValueString, string $comparedValueType): void
    {
        $constraint = $this->createConstraint(['value' => $comparedValue, 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]);
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

    public function testOptionsCanBeSetAsNamedArguments(): void
    {
        $constraint = $this->createConstraint([
            'value' => 100,
            'currency' => 'EUR',
            'formatterFormat' => Format::DECIMAL,
            'parserFormat' => Format::INTL_LOCALIZED_DECIMAL,
            'fractionDigits' => 3,
            'groupingUsed' => false,
            'locale' => 'de',
            'style' => FormatterFactoryInterface::STYLE_DECIMAL,
        ]);

        self::assertSame('EUR', $constraint->currency);
        self::assertSame(Format::DECIMAL, $constraint->formatterFormat);
        self::assertSame(Format::INTL_LOCALIZED_DECIMAL, $constraint->parserFormat);
        self::assertSame(3, $constraint->fractionDigits);
        self::assertFalse($constraint->groupingUsed);
        self::assertSame('de', $constraint->locale);
        self::assertSame(FormatterFactoryInterface::STYLE_DECIMAL, $constraint->style);
    }

    public function testNamedArgumentOptionsAreUsedForValidation(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $formatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $dirtyValue = new Money($dirtyValue->getAmount(), new Currency('EUR'));
        $comparedValue = $formatter->format(new Money($comparedValue->getAmount(), new Currency('EUR')));

        $constraint = $this->createConstraint([
            'value' => $comparedValue,
            'message' => 'Constraint Message',
            'currency' => 'EUR',
            'formatterFormat' => Format::DECIMAL,
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', $formatter->format($dirtyValue))
            ->setParameter('{{ compared_value }}', $comparedValue)
            ->setParameter('{{ compared_value_type }}', 'string')
            ->setCode($this->getErrorCode())
            ->assertRaised();
    }

    public function testScalarComparedValueUsesTheCurrencyOfTheValidatedValue(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $formatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $dirtyValue = new Money($dirtyValue->getAmount(), new Currency('EUR'));
        $comparedValue = $formatter->format(new Money($comparedValue->getAmount(), new Currency('EUR')));

        $constraint = $this->createConstraint([
            'value' => $comparedValue,
            'message' => 'Constraint Message',
            'formatterFormat' => Format::DECIMAL,
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', $formatter->format($dirtyValue))
            ->setParameter('{{ compared_value }}', $comparedValue)
            ->setParameter('{{ compared_value_type }}', 'string')
            ->setCode($this->getErrorCode())
            ->assertRaised();
    }

    public function testScalarValidatedValueUsesTheCurrencyOfTheComparedValue(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $formatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $dirtyValue = $formatter->format(new Money($dirtyValue->getAmount(), new Currency('EUR')));
        $comparedValue = new Money($comparedValue->getAmount(), new Currency('EUR'));

        $constraint = $this->createConstraint([
            'value' => $comparedValue,
            'message' => 'Constraint Message',
            'formatterFormat' => Format::DECIMAL,
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', $dirtyValue)
            ->setParameter('{{ compared_value }}', $formatter->format($comparedValue))
            ->setParameter('{{ compared_value_type }}', Money::class)
            ->setCode($this->getErrorCode())
            ->assertRaised();
    }

    #[RequiresPhpExtension('intl')]
    public function testValuesAreFormattedWithTheFractionDigitsOfTheirCurrency(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $dirtyValue = new Money($dirtyValue->getAmount(), new Currency('JPY'));
        $comparedValue = new Money($comparedValue->getAmount(), new Currency('JPY'));

        // The currency style applies the currency's fraction digits unless they are set
        $formatter = new IntlMoneyFormatter(new \NumberFormatter('en', \NumberFormatter::CURRENCY), new ISOCurrencies());

        $constraint = $this->createConstraint([
            'value' => $comparedValue,
            'message' => 'Constraint Message',
            'locale' => 'en',
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', $formatter->format($dirtyValue))
            ->setParameter('{{ compared_value }}', $formatter->format($comparedValue))
            ->setParameter('{{ compared_value_type }}', Money::class)
            ->setCode($this->getErrorCode())
            ->assertRaised();
    }

    /**
     * @return \Generator<string, array{'int'|'float'|'string'}>
     */
    public static function provideScalarTypes(): \Generator
    {
        yield 'integer' => ['int'];
        yield 'float' => ['float'];
        yield 'integer string' => ['string'];
    }

    /**
     * @param 'int'|'float'|'string' $type
     */
    #[DataProvider('provideScalarTypes')]
    public function testComparedScalarValueInMajorUnits(string $type): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $this->validateAndAssertViolation(
            $dirtyValue,
            $comparedValue,
            ['value' => $this->toMajorUnitScalar($comparedValue, $type), 'scalarUnit' => AbstractMoneyComparison::UNIT_MAJOR],
            $type,
        );
    }

    /**
     * @param 'int'|'float'|'string' $type
     */
    #[DataProvider('provideScalarTypes')]
    public function testValidatedScalarValueInMajorUnits(string $type): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $this->validateAndAssertViolation(
            $this->toMajorUnitScalar($dirtyValue, $type),
            $comparedValue,
            ['value' => $comparedValue, 'scalarUnit' => AbstractMoneyComparison::UNIT_MAJOR],
            Money::class,
            $dirtyValue,
        );
    }

    public function testMajorUnitsSupportFractionalFloats(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        // Shift both values by the same fractional amount to keep the comparison's outcome
        $dirtyValue = $dirtyValue->add(Money::USD(50));
        $comparedValue = $comparedValue->add(Money::USD(50));

        $this->validateAndAssertViolation(
            $dirtyValue,
            $comparedValue,
            ['value' => (float) new DecimalMoneyFormatter(new ISOCurrencies())->format($comparedValue), 'scalarUnit' => AbstractMoneyComparison::UNIT_MAJOR],
            'float',
        );
    }

    public function testMinorUnitsRejectFractionalFloats(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Could not convert value "2.5" to a "%s" instance for comparison.', Money::class));

        $this->validator->validate(Money::USD(500), $this->createConstraint(['value' => 2.5, 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]));
    }

    public function testScalarValuesUseMinorUnitsWithADeprecationWhenTheScalarUnitIsNotSet(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $constraint = $this->createConstraint(['value' => (int) $comparedValue->getAmount()]);

        $this->expectUserDeprecationMessage(\sprintf('Since babdev/money-bundle 3.2: Comparing the scalar value "%s" with the "%s" constraint without setting the "scalarUnit" option is deprecated, the value is treated as an amount in minor units. In 4.0, the default will change to major units; set the option to "minor" to keep the current behavior or "major" to opt in to the new behavior.', $comparedValue->getAmount(), $constraint::class));

        $this->validateAndAssertViolation($dirtyValue, $comparedValue, ['value' => (int) $comparedValue->getAmount()], 'int');
    }

    /**
     * @return \Generator<string, array{float|int|string}>
     */
    public static function provideZeroValues(): \Generator
    {
        yield 'integer' => [0];
        yield 'float' => [0.0];
        yield 'integer string' => ['0'];
        yield 'negative integer string' => ['-0'];
    }

    #[DataProvider('provideZeroValues')]
    public function testZeroDoesNotTriggerTheScalarUnitDeprecation(float|int|string $value): void
    {
        $deprecations = [];

        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $this->validator->validate(Money::USD(500), $this->createConstraint(['value' => $value]));
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $deprecations);
    }

    public function testThrowsConstraintExceptionForAnInvalidScalarUnit(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('constraint requires the "scalarUnit" option to be one of "minor" or "major", "cents" given.');

        $this->createConstraint(['value' => 0, 'scalarUnit' => 'cents']);
    }

    public function testFormattedStringsAreParsedWithTheParserFormat(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $dirtyValue = new Money($dirtyValue->getAmount(), new Currency('EUR'));
        $comparedValue = new Money($comparedValue->getAmount(), new Currency('EUR'));

        // German formatting has no "." for these amounts (i.e. "3,00 €"), which previously skipped the parser
        $formattedValue = new FormatterFactory('en')->createFormatter(Format::INTL_MONEY, 'de')->format($comparedValue);

        $this->validateAndAssertViolation(
            $dirtyValue,
            $comparedValue,
            ['value' => $formattedValue, 'parserFormat' => Format::INTL_MONEY, 'locale' => 'de', 'currency' => 'EUR'],
            'string',
        );
    }

    /**
     * Validates the value with the given constraint options and asserts the constraint's violation is raised, with values in violation messages formatted as decimals.
     *
     * @param array<string, mixed> $options
     */
    private function validateAndAssertViolation(Money|float|int|string $dirtyValue, Money $comparedValue, array $options, string $comparedValueType, ?Money $dirtyValueAsMoney = null): void
    {
        $formatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $constraint = $this->createConstraint([
            ...$options,
            'message' => 'Constraint Message',
            'formatterFormat' => Format::DECIMAL,
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $dirtyValueAsMoney ??= $dirtyValue;

        self::assertInstanceOf(Money::class, $dirtyValueAsMoney);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', $formatter->format($dirtyValueAsMoney))
            ->setParameter('{{ compared_value }}', $formatter->format($comparedValue))
            ->setParameter('{{ compared_value_type }}', $comparedValueType)
            ->setCode($this->getErrorCode())
            ->assertRaised();
    }

    /**
     * @param 'int'|'float'|'string' $type
     */
    private function toMajorUnitScalar(Money $money, string $type): float|int|string
    {
        $decimal = new DecimalMoneyFormatter(new ISOCurrencies())->format($money);

        return match ($type) {
            'int' => (int) $decimal,
            'float' => (float) $decimal,
            'string' => (string) (int) $decimal,
        };
    }

    #[DataProvider('provideComparisonsToNullValueAtPropertyPath')]
    public function testCompareWithNullValueAtPropertyAt(Money|float|int|string|null $dirtyValue, string $dirtyValueAsString, bool $isValid): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'value', 'scalarUnit' => AbstractMoneyComparison::UNIT_MINOR]);
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
