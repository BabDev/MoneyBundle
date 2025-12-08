<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use BabDev\MoneyBundle\Validator\Constraints\MoneyNotEqualTo;
use BabDev\MoneyBundle\Validator\Constraints\MoneyNotEqualToValidator;
use Money\Money;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\ConstraintValidatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class MoneyNotEqualToValidatorTest extends AbstractMoneyComparisonValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new MoneyNotEqualToValidator(new FormatterFactory('en'), new ParserFactory('en'), 'USD');
    }

    /**
     * @param array<string, mixed>|null $options An array of named arguments for the constraint or null for no arguments
     */
    protected function createConstraint(?array $options = null): AbstractMoneyComparison
    {
        if (null !== $options) {
            return new MoneyNotEqualTo(...$options); // @phpstan-ignore-line argument.type
        }

        return new MoneyNotEqualTo();
    }

    protected function getErrorCode(): string
    {
        return MoneyNotEqualTo::IS_EQUAL_ERROR;
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null, Money|float|int|string|null}>
     */
    public static function provideValidComparisons(): \Generator
    {
        yield 'different values as integers' => [300, 200];
        yield 'different values as floats' => [300.0, 200.0];
        yield 'different values as non-formatted strings' => ['300', '200'];
        yield 'different values as formatted strings' => ['3.00', '2.00'];
        yield \sprintf('different values as %s objects', Money::class) => [Money::USD(300), Money::USD(200)];
        yield 'different values as different data types' => ['3.00', 200];
        yield 'null input value' => [null, Money::USD(200)];
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null}>
     */
    public static function provideValidComparisonsToPropertyPath(): \Generator
    {
        yield 'value as integer' => [600];
        yield 'value as float' => [600.0];
        yield 'value as non-formatted string' => ['600'];
        yield 'value as formatted string' => ['6.00'];
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null, string, Money|float|int|string|null, string, string|class-string<Money>}>
     */
    public static function provideInvalidComparisons(): \Generator
    {
        yield 'values as integers' => [200, '$2.00', 200, '$2.00', 'int'];
        yield 'values as floats' => [200.0, '$2.00', 200.0, '$2.00', 'float'];
        yield 'values as non-formatted strings' => [200, '$2.00', 200, '$2.00', 'int'];
        yield 'values as formatted strings' => ['2.00', '$2.00', '2.00', '$2.00', 'string'];
        yield \sprintf('values as %s objects', Money::class) => [Money::USD(200), '$2.00', Money::USD(200), '$2.00', Money::class];
        yield 'values as different data types' => ['2.00', '$2.00', 200, '$2.00', 'int'];
    }

    /**
     * @return array{Money, non-empty-string, Money, non-empty-string, class-string<Money>}
     */
    public function provideInvalidComparisonToPropertyPath(): array
    {
        return [
            Money::USD(200),
            '$2.00',
            Money::USD(200),
            '$2.00',
            Money::class,
        ];
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null, string, bool}>
     */
    public static function provideComparisonsToNullValueAtPropertyPath(): \Generator
    {
        yield 'valid null comparison' => [Money::USD(500), '$5.00', true];
    }
}
