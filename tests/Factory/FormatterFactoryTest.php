<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Factory;

use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Formatter\CurrencyFractionDigitsFormatter;
use Money\Formatter\AggregateMoneyFormatter;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

final class FormatterFactoryTest extends TestCase
{
    private readonly FormatterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FormatterFactory('en_US');
    }

    public function testAggregateFormatterIsNotSupported(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage(\sprintf('The "%s" class is not supported by "%s".', AggregateMoneyFormatter::class, FormatterFactory::class));

        $this->factory->createFormatter(Format::AGGREGATE);
    }

    public function testBitcoinFormatterIsCreated(): void
    {
        self::assertInstanceOf(BitcoinMoneyFormatter::class, $this->factory->createFormatter(Format::BITCOIN));
    }

    public function testDecimalFormatterIsCreated(): void
    {
        self::assertInstanceOf(DecimalMoneyFormatter::class, $this->factory->createFormatter(Format::DECIMAL));
    }

    #[RequiresPhpExtension('intl')]
    public function testIntlLocalizedDecimalFormatterIsCreated(): void
    {
        self::assertInstanceOf(CurrencyFractionDigitsFormatter::class, $this->factory->createFormatter(Format::INTL_LOCALIZED_DECIMAL));
    }

    #[RequiresPhpExtension('intl')]
    public function testIntlLocalizedDecimalFormatterIsCreatedWithFractionDigits(): void
    {
        self::assertInstanceOf(IntlLocalizedDecimalFormatter::class, $this->factory->createFormatter(Format::INTL_LOCALIZED_DECIMAL, null, ['fraction_digits' => 2]));
    }

    #[RequiresPhpExtension('intl')]
    public function testIntlMoneyFormatterIsCreated(): void
    {
        self::assertInstanceOf(CurrencyFractionDigitsFormatter::class, $this->factory->createFormatter(Format::INTL_MONEY));
    }

    #[RequiresPhpExtension('intl')]
    public function testIntlMoneyFormatterIsCreatedWithFractionDigits(): void
    {
        self::assertInstanceOf(IntlMoneyFormatter::class, $this->factory->createFormatter(Format::INTL_MONEY, null, ['fraction_digits' => 2]));
    }

    /**
     * @return \Generator<string, array{Format::*, array{fraction_digits?: int<0, max>, style?: string}, list<array{Money, string}>}>
     */
    public static function provideIntlFormats(): \Generator
    {
        yield 'money with the currency style' => [Format::INTL_MONEY, [], [[Money::USD(123450), '$1,234.50'], [Money::JPY(1234), '¥1,234'], [Money::USD(123400), '$1,234.00']]];
        yield 'money with the decimal style' => [Format::INTL_MONEY, ['style' => 'decimal'], [[Money::USD(123450), '1,234.50'], [Money::JPY(1234), '1,234'], [Money::BHD(1234), '1.234'], [Money::USD(123400), '1,234.00']]];
        yield 'localized decimal' => [Format::INTL_LOCALIZED_DECIMAL, ['style' => 'decimal'], [[Money::USD(123450), '1,234.50'], [Money::JPY(1234), '1,234'], [Money::BHD(1234), '1.234'], [Money::USD(123400), '1,234.00']]];
        yield 'money with fraction digits' => [Format::INTL_MONEY, ['fraction_digits' => 2], [[Money::JPY(1234), '¥1,234.00'], [Money::USD(123450), '$1,234.50']]];
        yield 'localized decimal with fraction digits' => [Format::INTL_LOCALIZED_DECIMAL, ['fraction_digits' => 2, 'style' => 'decimal'], [[Money::JPY(1234), '1,234.00'], [Money::BHD(1234), '1.23']]];
    }

    /**
     * @param array{fraction_digits?: int<0, max>, style?: string} $options
     * @param list<array{Money, string}>                           $expectations
     *
     * @phpstan-param Format::* $format
     */
    #[DataProvider('provideIntlFormats')]
    #[RequiresPhpExtension('intl')]
    public function testIntlFormattersUseTheFractionDigitsOfTheCurrencyByDefault(string $format, array $options, array $expectations): void
    {
        $formatter = $this->factory->createFormatter($format, null, $options);

        foreach ($expectations as [$money, $expected]) {
            self::assertSame($expected, $formatter->format($money));
        }
    }

    public function testFormatterIsNotCreatedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported"');

        $this->factory->createFormatter('unsupported'); // @phpstan-ignore-line argument.type
    }
}
