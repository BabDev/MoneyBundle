<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Factory;

use BabDev\MoneyBundle\Factory\FormatterFactory;
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

    public function testBitcoinFormatterIsCreated(): void
    {
        $this->assertInstanceOf(BitcoinMoneyFormatter::class, $this->factory->createFormatter('bitcoin'));
    }

    public function testDecimalFormatterIsCreated(): void
    {
        $this->assertInstanceOf(DecimalMoneyFormatter::class, $this->factory->createFormatter('decimal'));
    }

    /**
     * @requires extension intl
     */
    public function testIntlLocalizedDecimalFormatterIsCreated(): void
    {
        $this->assertInstanceOf(IntlLocalizedDecimalFormatter::class, $this->factory->createFormatter('intl_localized_decimal'));
    }

    /**
     * @requires extension intl
     */
    public function testIntlMoneyFormatterIsCreated(): void
    {
        $this->assertInstanceOf(IntlMoneyFormatter::class, $this->factory->createFormatter('intl_money'));
    }

    public function testFormatterIsNotCreatedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported", allowed formats: [bitcoin, decimal, intl_localized_decimal, intl_money]');

        $this->factory->createFormatter('unsupported');
    }
}
