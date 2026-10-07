<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Factory;

use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Format;
use Money\Currencies\CurrencyList;
use Money\Currency;
use Money\Money;
use Money\Parser\AggregateMoneyParser;
use Money\Parser\BitcoinMoneyParser;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Money\Parser\IntlMoneyParser;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

final class ParserFactoryTest extends TestCase
{
    private readonly ParserFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ParserFactory('en_US');
    }

    public function testAggregateParserIsNotSupported(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage(\sprintf('The "%s" class is not supported by "%s".', AggregateMoneyParser::class, ParserFactory::class));

        $this->factory->createParser(Format::AGGREGATE);
    }

    public function testBitcoinParserIsCreated(): void
    {
        self::assertInstanceOf(BitcoinMoneyParser::class, $this->factory->createParser(Format::BITCOIN));
    }

    public function testDecimalParserIsCreated(): void
    {
        self::assertInstanceOf(DecimalMoneyParser::class, $this->factory->createParser(Format::DECIMAL));
    }

    #[RequiresPhpExtension('intl')]
    public function testIntlLocalizedDecimalParserIsCreated(): void
    {
        self::assertInstanceOf(IntlLocalizedDecimalParser::class, $this->factory->createParser(Format::INTL_LOCALIZED_DECIMAL));
    }

    #[RequiresPhpExtension('intl')]
    public function testIntlMoneyParserIsCreated(): void
    {
        self::assertInstanceOf(IntlMoneyParser::class, $this->factory->createParser(Format::INTL_MONEY));
    }

    #[RequiresPhpExtension('intl')]
    public function testIntlLocalizedDecimalParserUsesTheDecimalStyleByDefault(): void
    {
        $currency = new Currency('EUR');

        self::assertEquals(new Money(123450, $currency), $this->factory->createParser(Format::INTL_LOCALIZED_DECIMAL)->parse('1,234.50', $currency));
    }

    #[RequiresPhpExtension('intl')]
    public function testParsersUseTheGivenCurrencies(): void
    {
        $factory = new ParserFactory('en_US', new CurrencyList(['PTS' => 3]));
        $currency = new Currency('PTS');

        self::assertEquals(new Money(123456, $currency), $factory->createParser(Format::DECIMAL)->parse('123.456', $currency));
        self::assertEquals(new Money(123456, $currency), $factory->createParser(Format::INTL_LOCALIZED_DECIMAL, null, ['style' => 'decimal'])->parse('123.456', $currency));
    }

    public function testParserIsNotCreatedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported"');

        $this->factory->createParser('unsupported'); // @phpstan-ignore-line argument.type
    }
}
