<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Factory;

use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Format;
use Money\Parser\AggregateMoneyParser;
use Money\Parser\BitcoinMoneyParser;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Money\Parser\IntlMoneyParser;
use PHPUnit\Framework\TestCase;

final class ParserFactoryTest extends TestCase
{
    private ParserFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ParserFactory('en_US');
    }

    public function testAggregateParserIsNotSupported(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage(sprintf('The "%s" class is not supported by "%s".', AggregateMoneyParser::class, ParserFactory::class));

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

    /**
     * @requires extension intl
     */
    public function testIntlLocalizedDecimalParserIsCreated(): void
    {
        self::assertInstanceOf(IntlLocalizedDecimalParser::class, $this->factory->createParser(Format::INTL_LOCALIZED_DECIMAL));
    }

    /**
     * @requires extension intl
     */
    public function testIntlMoneyParserIsCreated(): void
    {
        self::assertInstanceOf(IntlMoneyParser::class, $this->factory->createParser(Format::INTL_MONEY));
    }

    public function testParserIsNotCreatedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported"');

        $this->factory->createParser('unsupported');
    }
}
