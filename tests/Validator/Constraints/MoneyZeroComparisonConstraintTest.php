<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparisonValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThan;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThanOrEqual;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThanOrEqualValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThanValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThan;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThanOrEqual;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThanOrEqualValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThanValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyNegative;
use BabDev\MoneyBundle\Validator\Constraints\MoneyNegativeOrZero;
use BabDev\MoneyBundle\Validator\Constraints\MoneyPositive;
use BabDev\MoneyBundle\Validator\Constraints\MoneyPositiveOrZero;
use Money\Money;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AbstractMoneyComparisonValidator>
 */
#[AllowMockObjectsWithoutExpectations]
final class MoneyZeroComparisonConstraintTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new MoneyGreaterThanValidator(new FormatterFactory('en'), new ParserFactory('en'), 'USD');
    }

    /**
     * @return \Generator<string, array{class-string<AbstractMoneyComparison>, class-string<AbstractMoneyComparisonValidator>, string}>
     */
    public static function provideConstraints(): \Generator
    {
        yield 'positive' => [MoneyPositive::class, MoneyGreaterThanValidator::class, 'This value should be positive.'];
        yield 'positive or zero' => [MoneyPositiveOrZero::class, MoneyGreaterThanOrEqualValidator::class, 'This value should be either positive or zero.'];
        yield 'negative' => [MoneyNegative::class, MoneyLessThanValidator::class, 'This value should be negative.'];
        yield 'negative or zero' => [MoneyNegativeOrZero::class, MoneyLessThanOrEqualValidator::class, 'This value should be either negative or zero.'];
    }

    /**
     * @param class-string<AbstractMoneyComparison>          $constraintClass
     * @param class-string<AbstractMoneyComparisonValidator> $validatorClass
     */
    #[DataProvider('provideConstraints')]
    public function testConstraintIsValidatedByTheComparisonValidator(string $constraintClass, string $validatorClass, string $message): void
    {
        $constraint = new $constraintClass();

        self::assertSame($validatorClass, $constraint->validatedBy());
        self::assertSame(0, $constraint->value);
        self::assertSame($message, $constraint->message);
    }

    /**
     * @return \Generator<string, array{class-string<AbstractMoneyComparison>, class-string<AbstractMoneyComparisonValidator>, string, Money|int|string, string}>
     */
    public static function provideValues(): \Generator
    {
        $values = [
            'positive' => [Money::USD(100), 500, '1.00'],
            'zero' => [Money::USD(0), 0, '0.00'],
            // A different currency than the default currency, which must not cause a currency mismatch
            'negative' => [Money::EUR(-100), -500, '-1.00'],
        ];

        $expectations = [
            MoneyPositive::class => [MoneyGreaterThanValidator::class, MoneyGreaterThan::TOO_LOW_ERROR, ['positive']],
            MoneyPositiveOrZero::class => [MoneyGreaterThanOrEqualValidator::class, MoneyGreaterThanOrEqual::TOO_LOW_ERROR, ['positive', 'zero']],
            MoneyNegative::class => [MoneyLessThanValidator::class, MoneyLessThan::TOO_HIGH_ERROR, ['negative']],
            MoneyNegativeOrZero::class => [MoneyLessThanOrEqualValidator::class, MoneyLessThanOrEqual::TOO_HIGH_ERROR, ['negative', 'zero']],
        ];

        foreach ($expectations as $constraintClass => [$validatorClass, $errorCode, $validSigns]) {
            foreach ($values as $sign => $signValues) {
                foreach ($signValues as $value) {
                    $name = \sprintf('%s with %s %s', substr((string) strrchr($constraintClass, '\\'), 1), $sign, get_debug_type($value));

                    yield $name => [$constraintClass, $validatorClass, \in_array($sign, $validSigns, true) ? '' : $errorCode, $value, $sign];
                }
            }
        }
    }

    /**
     * @param class-string<AbstractMoneyComparison>          $constraintClass
     * @param class-string<AbstractMoneyComparisonValidator> $validatorClass
     */
    #[DataProvider('provideValues')]
    public function testValuesAreComparedToZeroInTheirOwnCurrency(string $constraintClass, string $validatorClass, string $errorCode, Money|int|string $value, string $sign): void
    {
        $this->validator = new $validatorClass(new FormatterFactory('en'), new ParserFactory('en'), 'USD');
        $this->validator->initialize($this->context);

        $constraint = new $constraintClass(formatterFormat: Format::DECIMAL);

        $this->validator->validate($value, $constraint);

        if ('' === $errorCode) {
            $this->assertNoViolation();

            return;
        }

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', match ($sign) {
                'positive' => \is_int($value) ? '5.00' : '1.00',
                'zero' => '0.00',
                default => \is_int($value) ? '-5.00' : '-1.00',
            })
            ->setParameter('{{ compared_value }}', '0.00')
            ->setParameter('{{ compared_value_type }}', 'int')
            ->setCode($errorCode)
            ->assertRaised();
    }
}
