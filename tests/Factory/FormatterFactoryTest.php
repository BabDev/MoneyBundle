<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Factory;

use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Format;
use Money\Formatter\AggregateMoneyFormatter;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Formatter\IntlMoneyFormatter;
use PHPUnit\Framework\TestCase;

final class FormatterFactoryTest extends TestCase
{
    /**
     * @var FormatterFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new FormatterFactory('en_US');
    }

    public function testAggregateFormatterIsNotSupported(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage(sprintf('The "%s" class is not supported by "%s".', AggregateMoneyFormatter::class, FormatterFactory::class));

        $this->factory->createFormatter(Format::AGGREGATE);
    }

    public function testBitcoinFormatterIsCreated(): void
    {
        $this->assertInstanceOf(BitcoinMoneyFormatter::class, $this->factory->createFormatter(Format::BITCOIN));
    }

    public function testDecimalFormatterIsCreated(): void
    {
        $this->assertInstanceOf(DecimalMoneyFormatter::class, $this->factory->createFormatter(Format::DECIMAL));
    }

    /**
     * @requires extension intl
     */
    public function testIntlLocalizedDecimalFormatterIsCreated(): void
    {
        $this->assertInstanceOf(IntlLocalizedDecimalFormatter::class, $this->factory->createFormatter(Format::INTL_LOCALIZED_DECIMAL));
    }

    /**
     * @requires extension intl
     */
    public function testIntlMoneyFormatterIsCreated(): void
    {
        $this->assertInstanceOf(IntlMoneyFormatter::class, $this->factory->createFormatter(Format::INTL_MONEY));
    }

    public function testFormatterIsNotCreatedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported"');

        $this->factory->createFormatter('unsupported');
    }
}
