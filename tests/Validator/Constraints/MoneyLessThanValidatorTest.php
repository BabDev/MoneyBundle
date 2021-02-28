<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThan;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThanValidator;
use Money\Money;
use Symfony\Component\Validator\ConstraintValidatorInterface;

final class MoneyLessThanValidatorTest extends AbstractMoneyComparisonValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new MoneyLessThanValidator(new FormatterFactory('en'), new ParserFactory('en'), 'USD');
    }

    protected function createConstraint(?array $options = null): AbstractMoneyComparison
    {
        return new MoneyLessThan($options);
    }

    protected function getErrorCode(): ?string
    {
        return MoneyLessThan::TOO_HIGH_ERROR;
    }

    public function provideValidComparisons(): \Generator
    {
        yield 'different values as integers' => [100, 200];
        yield 'different values as floats' => [100.0, 200.0];
        yield 'different values as non-formatted strings' => ['100', '200'];
        yield 'different values as formatted strings' => ['1.00', '2.00'];
        yield sprintf('different values as %s objects', Money::class) => [Money::USD(100), Money::USD(200)];
        yield 'different values as different data types' => ['1.00', 200];
        yield 'null input value' => [null, Money::USD(200)];
    }

    public function provideValidComparisonsToPropertyPath(): \Generator
    {
        yield 'value as integer' => [400];
        yield 'value as float' => [400.0];
        yield 'value as non-formatted string' => ['400'];
        yield 'value as formatted string' => ['4.00'];
    }

    public function provideInvalidComparisons(): \Generator
    {
        yield 'values as integers' => [200, '$2.00', 100, '$1.00', 'int'];
        yield 'values as floats' => [200.0, '$2.00', 100.0, '$1.00', 'float'];
        yield 'values as non-formatted strings' => [200, '$2.00', 100, '$1.00', 'int'];
        yield 'values as formatted strings' => ['2.00', '$2.00', '1.00', '$1.00', 'string'];
        yield sprintf('values as %s objects', Money::class) => [Money::USD(200), '$2.00', Money::USD(100), '$1.00', Money::class];
        yield 'values as different data types' => ['2.00', '$2.00', 100, '$1.00', 'int'];
    }

    public function provideInvalidComparisonToPropertyPath(): array
    {
        return [
            Money::USD(200),
            '$2.00',
            Money::USD(100),
            '$1.00',
            Money::class,
        ];
    }

    public function provideComparisonsToNullValueAtPropertyPath(): \Generator
    {
        yield 'valid null comparison' => [Money::USD(500), '$5.00', true];
    }
}
