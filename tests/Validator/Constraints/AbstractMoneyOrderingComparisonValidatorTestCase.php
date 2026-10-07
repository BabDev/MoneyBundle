<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;

/**
 * Provides a base class for the validation of property comparisons which order values, and therefore require values to have the same currency.
 */
abstract class AbstractMoneyOrderingComparisonValidatorTestCase extends AbstractMoneyComparisonValidatorTestCase
{
    public function testCurrencyMismatchToValue(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $formatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $comparedValue = new Money($comparedValue->getAmount(), new Currency('EUR'));

        $constraint = $this->createConstraint([
            'value' => $comparedValue,
            'currencyMismatchMessage' => 'Currency Mismatch Message',
            'formatterFormat' => Format::DECIMAL,
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Currency Mismatch Message')
            ->setParameter('{{ value }}', $formatter->format($dirtyValue))
            ->setParameter('{{ compared_value }}', $formatter->format($comparedValue))
            ->setParameter('{{ compared_value_type }}', Money::class)
            ->setCode(AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testCurrencyMismatchToPropertyPath(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $formatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $comparedValue = new Money($comparedValue->getAmount(), new Currency('EUR'));

        $constraint = $this->createConstraint([
            'propertyPath' => 'value',
            'currencyMismatchMessage' => 'Currency Mismatch Message',
            'formatterFormat' => Format::DECIMAL,
        ]);

        $this->setObject($this->createValueObject($comparedValue));

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Currency Mismatch Message')
            ->setParameter('{{ value }}', $formatter->format($dirtyValue))
            ->setParameter('{{ compared_value }}', $formatter->format($comparedValue))
            ->setParameter('{{ compared_value_path }}', 'value')
            ->setParameter('{{ compared_value_type }}', Money::class)
            ->setCode(AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testCurrencyMismatchToScalarValueWithConstraintCurrency(): void
    {
        [$dirtyValue, , $comparedValue] = $this->provideInvalidComparisonToPropertyPath();

        $formatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $comparedValue = $formatter->format($comparedValue);

        $constraint = $this->createConstraint([
            'value' => $comparedValue,
            'currency' => 'EUR',
            'currencyMismatchMessage' => 'Currency Mismatch Message',
            'formatterFormat' => Format::DECIMAL,
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Currency Mismatch Message')
            ->setParameter('{{ value }}', $formatter->format($dirtyValue))
            ->setParameter('{{ compared_value }}', $comparedValue)
            ->setParameter('{{ compared_value_type }}', 'string')
            ->setCode(AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR)
            ->assertRaised();
    }

    public function testCurrencyMismatchErrorHasAName(): void
    {
        self::assertSame('CURRENCY_MISMATCH_ERROR', $this->createConstraint(['value' => 0])::getErrorName(AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR));
    }
}
