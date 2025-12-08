<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Validator\Constraints;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparison;
use BabDev\MoneyBundle\Validator\Constraints\MoneyEqualTo;
use BabDev\MoneyBundle\Validator\Constraints\MoneyEqualToValidator;
use Money\Money;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\ConstraintValidatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class MoneyEqualToValidatorTest extends AbstractMoneyComparisonValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new MoneyEqualToValidator(new FormatterFactory('en'), new ParserFactory('en'), 'USD');
    }

    /**
     * @param array<string, mixed>|null $options An array of named arguments for the constraint or null for no arguments
     */
    protected function createConstraint(?array $options = null): AbstractMoneyComparison
    {
        if (null !== $options) {
            return new MoneyEqualTo(...$options); // @phpstan-ignore-line argument.type
        }

        return new MoneyEqualTo();
    }

    protected function getErrorCode(): string
    {
        return MoneyEqualTo::NOT_EQUAL_ERROR;
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null, Money|float|int|string|null}>
     */
    public static function provideValidComparisons(): \Generator
    {
        yield 'same values as integers' => [300, 300];
        yield 'same values as floats' => [300.0, 300.0];
        yield 'same values as non-formatted strings' => ['300', '300'];
        yield 'same values as formatted strings' => ['3.00', '3.00'];
        yield \sprintf('same values as %s objects', Money::class) => [Money::USD(300), Money::USD(300)];
        yield 'same values as different data types' => ['3.00', 300];
        yield 'null input value' => [null, Money::USD(300)];
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null}>
     */
    public static function provideValidComparisonsToPropertyPath(): \Generator
    {
        yield 'value as integer' => [500];
        yield 'value as float' => [500.0];
        yield 'value as non-formatted string' => ['500'];
        yield 'value as formatted string' => ['5.00'];
    }

    /**
     * @return \Generator<string, array{Money|float|int|string|null, string, Money|float|int|string|null, string, string|class-string<Money>}>
     */
    public static function provideInvalidComparisons(): \Generator
    {
        yield 'values as integers' => [200, '$2.00', 300, '$3.00', 'int'];
        yield 'values as floats' => [200.0, '$2.00', 300.0, '$3.00', 'float'];
        yield 'values as non-formatted strings' => [200, '$2.00', 300, '$3.00', 'int'];
        yield 'values as formatted strings' => ['2.00', '$2.00', '3.00', '$3.00', 'string'];
        yield \sprintf('values as %s objects', Money::class) => [Money::USD(200), '$2.00', Money::USD(300), '$3.00', Money::class];
        yield 'values as different data types' => ['2.00', '$2.00', 300, '$3.00', 'int'];
    }

    /**
     * @return array{Money, non-empty-string, Money, non-empty-string, class-string<Money>}
     */
    public function provideInvalidComparisonToPropertyPath(): array
    {
        return [
            Money::USD(200),
            '$2.00',
            Money::USD(300),
            '$3.00',
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
