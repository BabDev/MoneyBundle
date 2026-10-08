<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Validator\Constraints\MoneyRange;
use BabDev\MoneyBundle\Validator\Constraints\MoneyRangeValidator;
use Money\Money;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<MoneyRangeValidator>
 */
#[AllowMockObjectsWithoutExpectations]
final class MoneyRangeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new MoneyRangeValidator(new FormatterFactory('en'), new ParserFactory('en'), 'USD');
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidDefinitions(): \Generator
    {
        yield 'no limits' => [[], 'requires at least one of the "min", "minPropertyPath", "max", or "maxPropertyPath" options to be set.'];
        yield 'min and minPropertyPath' => [['min' => 100, 'minPropertyPath' => 'min'], 'requires only one of the "min" or "minPropertyPath" options to be set, not both.'];
        yield 'max and maxPropertyPath' => [['max' => 100, 'maxPropertyPath' => 'max'], 'requires only one of the "max" or "maxPropertyPath" options to be set, not both.'];
        yield 'minMessage with both limits' => [['min' => 100, 'max' => 1000, 'minMessage' => 'Too low'], 'can not use the "minMessage" and "maxMessage" options when both a minimum and maximum are set, use the "notInRangeMessage" option instead.'];
        yield 'invalid scalar unit' => [['min' => 100, 'scalarUnit' => 'cents'], 'requires the "scalarUnit" option to be one of "minor" or "major", "cents" given.'];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideInvalidDefinitions')]
    public function testConstraintDefinitionIsValidated(array $options, string $message): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage(\sprintf('The "%s" constraint %s', MoneyRange::class, $message));

        new MoneyRange(...$options); // @phpstan-ignore-line argument.type
    }

    public function testConstraintIsValidatedByTheRangeValidator(): void
    {
        self::assertSame(MoneyRangeValidator::class, new MoneyRange(min: 0, scalarUnit: MoneyRange::UNIT_MINOR)->validatedBy());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new MoneyRange(min: 100, max: 1000, scalarUnit: MoneyRange::UNIT_MINOR));

        $this->assertNoViolation();
    }

    /**
     * @return \Generator<string, array{Money|string}>
     */
    public static function provideValuesInRange(): \Generator
    {
        yield 'minimum' => [Money::USD(100)];
        yield 'between' => [Money::USD(500)];
        yield 'maximum' => [Money::USD(1000)];
        yield 'formatted string' => ['5.00'];
    }

    #[DataProvider('provideValuesInRange')]
    public function testValuesInRangeAreValid(Money|string $value): void
    {
        $this->validator->validate($value, new MoneyRange(min: Money::USD(100), max: Money::USD(1000)));

        $this->assertNoViolation();
    }

    /**
     * @return \Generator<string, array{Money, string}>
     */
    public static function provideValuesNotInRange(): \Generator
    {
        yield 'less than the minimum' => [Money::USD(99), '0.99'];
        yield 'greater than the maximum' => [Money::USD(1001), '10.01'];
    }

    #[DataProvider('provideValuesNotInRange')]
    public function testValuesNotInRangeAreInvalid(Money $value, string $formattedValue): void
    {
        $constraint = new MoneyRange(min: '1.00', max: '10.00', formatterFormat: Format::DECIMAL);

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ min }}', '1.00')
            ->setParameter('{{ max }}', '10.00')
            ->setParameter('{{ value }}', $formattedValue)
            ->setCode(MoneyRange::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testValueLessThanTheMinimumIsInvalid(): void
    {
        $constraint = new MoneyRange(min: 100, formatterFormat: Format::DECIMAL, scalarUnit: MoneyRange::UNIT_MAJOR);

        $this->validator->validate(Money::USD(9999), $constraint);

        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ limit }}', '100.00')
            ->setParameter('{{ value }}', '99.99')
            ->setCode(MoneyRange::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testValueGreaterThanTheMaximumIsInvalid(): void
    {
        $constraint = new MoneyRange(max: 100, formatterFormat: Format::DECIMAL, scalarUnit: MoneyRange::UNIT_MAJOR);

        $this->validator->validate(Money::USD(10001), $constraint);

        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '100.00')
            ->setParameter('{{ value }}', '100.01')
            ->setCode(MoneyRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testLocalizedDecimalFormatUsesTheDecimalStyleByDefault(): void
    {
        $constraint = new MoneyRange(min: Money::EUR(100000), formatterFormat: Format::INTL_LOCALIZED_DECIMAL, locale: 'en');

        self::assertNull($constraint->style);

        $this->validator->validate(Money::EUR(99950), $constraint);

        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ limit }}', '1,000.00')
            ->setParameter('{{ value }}', '999.50')
            ->setCode(MoneyRange::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testScalarLimitsUseTheCurrencyOfTheValue(): void
    {
        $constraint = new MoneyRange(min: 100, max: 1000, formatterFormat: Format::DECIMAL, scalarUnit: MoneyRange::UNIT_MINOR);

        $this->validator->validate(Money::EUR(500), $constraint);

        $this->assertNoViolation();
    }

    public function testLimitsInADifferentCurrencyAreAMismatch(): void
    {
        $constraint = new MoneyRange(min: Money::USD(100), max: Money::USD(1000), formatterFormat: Format::DECIMAL);

        $this->validator->validate(Money::EUR(500), $constraint);

        $this->buildViolation($constraint->currencyMismatchMessage)
            ->setParameter('{{ value }}', '5.00')
            ->setParameter('{{ compared_value }}', '1.00')
            ->setCode(MoneyRange::CURRENCY_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testLimitsAreReadFromPropertyPaths(): void
    {
        $object = new class(Money::USD(100), Money::USD(1000)) {
            public function __construct(
                public Money $min,
                public Money $max,
            ) {}
        };

        $this->setObject($object);

        $constraint = new MoneyRange(minPropertyPath: 'min', maxPropertyPath: 'max', formatterFormat: Format::DECIMAL);

        $this->validator->validate(Money::USD(50), $constraint);

        $this->buildViolation($constraint->notInRangeMessage)
            ->setParameter('{{ min }}', '1.00')
            ->setParameter('{{ max }}', '10.00')
            ->setParameter('{{ value }}', '0.50')
            ->setParameter('{{ min_limit_path }}', 'min')
            ->setParameter('{{ max_limit_path }}', 'max')
            ->setCode(MoneyRange::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }

    public function testUninitializedPropertyPathsAreNotLimits(): void
    {
        $object = new class(Money::USD(1000)) {
            public Money $min;

            public function __construct(
                public Money $max,
            ) {}
        };

        $this->setObject($object);

        $constraint = new MoneyRange(minPropertyPath: 'min', maxPropertyPath: 'max', formatterFormat: Format::DECIMAL);

        $this->validator->validate(Money::USD(50), $constraint);

        $this->assertNoViolation();

        $this->validator->validate(Money::USD(1001), $constraint);

        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '10.00')
            ->setParameter('{{ value }}', '10.01')
            ->setParameter('{{ min_limit_path }}', 'min')
            ->setParameter('{{ max_limit_path }}', 'max')
            ->setCode(MoneyRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testScalarLimitsWithoutScalarUnitAreDeprecated(): void
    {
        $this->expectUserDeprecationMessage(\sprintf('Since babdev/money-bundle 3.2: Comparing the scalar value "100" with the "%s" constraint without setting the "scalarUnit" option is deprecated, the value is treated as an amount in minor units. In 4.0, the default will change to major units; set the option to "minor" to keep the current behavior or "major" to opt in to the new behavior.', MoneyRange::class));

        $this->validator->validate(Money::USD(500), new MoneyRange(min: 100));

        $this->assertNoViolation();
    }

    public function testUnsupportedValuesAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(['amount' => 500], new MoneyRange(min: Money::USD(100)));
    }

    public function testUnconvertibleValuesAddAViolation(): void
    {
        $this->validator->validate('test', new MoneyRange(min: Money::USD(100), invalidMessage: 'Invalid Message'));

        $this->buildViolation('Invalid Message')
            ->setParameter('{{ value }}', '"test"')
            ->setCode(MoneyRange::INVALID_VALUE_ERROR)
            ->assertRaised();
    }

    public function testUnconvertibleLimitThrowsWhenTheValidatedValueIsAlsoUnconvertible(): void
    {
        $this->setObject(new class {
            public string $max = 'INVALID';
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Could not convert value "INVALID" to a "%s" instance for comparison.', Money::class));

        $this->validator->validate('test', new MoneyRange(maxPropertyPath: 'max'));
    }

    public function testInvalidValueErrorHasAName(): void
    {
        self::assertSame('INVALID_VALUE_ERROR', MoneyRange::getErrorName(MoneyRange::INVALID_VALUE_ERROR));
    }

    public function testUnsupportedLimitsFromAPropertyPathAreRejected(): void
    {
        $this->setObject(new class {
            /**
             * @var list<int>
             */
            public array $max = [500];
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The value of the "max" property path provided to the "%s" constraint must be a "%s" instance, an integer, a float, or a string, "array" given.', MoneyRange::class, Money::class));

        $this->validator->validate(Money::USD(500), new MoneyRange(maxPropertyPath: 'max'));
    }
}
