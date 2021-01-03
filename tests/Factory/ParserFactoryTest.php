<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Factory;

use BabDev\MoneyBundle\Factory\ParserFactory;
use Money\Parser\BitcoinMoneyParser;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Money\Parser\IntlMoneyParser;
use PHPUnit\Framework\TestCase;

final class ParserFactoryTest extends TestCase
{
    /**
     * @var ParserFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new ParserFactory('en_US');
    }

    public function testBitcoinParserIsCreated(): void
    {
        $this->assertInstanceOf(BitcoinMoneyParser::class, $this->factory->createParser('bitcoin'));
    }

    public function testDecimalParserIsCreated(): void
    {
        $this->assertInstanceOf(DecimalMoneyParser::class, $this->factory->createParser('decimal'));
    }

    /**
     * @requires extension intl
     */
    public function testIntlLocalizedDecimalParserIsCreated(): void
    {
        $this->assertInstanceOf(IntlLocalizedDecimalParser::class, $this->factory->createParser('intl_localized_decimal'));
    }

    /**
     * @requires extension intl
     */
    public function testIntlMoneyParserIsCreated(): void
    {
        $this->assertInstanceOf(IntlMoneyParser::class, $this->factory->createParser('intl_money'));
    }

    public function testParserIsNotCreatedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported", allowed formats: [bitcoin, decimal, intl_localized_decimal, intl_money]');

        $this->factory->createParser('unsupported');
    }
}
