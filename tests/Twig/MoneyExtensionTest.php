<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Twig;

use BabDev\MoneyBundle\Twig\MoneyExtension;
use Money\Currencies\BitcoinCurrencies;
use Money\Money;
use PHPUnit\Framework\TestCase;

final class MoneyExtensionTest extends TestCase
{
    /**
     * @var MoneyExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new MoneyExtension('en_US');
    }

    public function testMoneyIsFormattedAsBitcoin(): void
    {
        $this->assertSame(BitcoinCurrencies::SYMBOL.'0.00000100', $this->extension->formatMoney(Money::XBT(100), 'bitcoin'));
    }

    public function testMoneyIsFormattedAsDecimal(): void
    {
        $this->assertSame('1.00', $this->extension->formatMoney(Money::USD(100), 'decimal'));
    }

    /**
     * @requires extension intl
     */
    public function testMoneyIsFormattedAsIntlLocalizedDecimal(): void
    {
        $this->assertSame('$1.00', $this->extension->formatMoney(Money::USD(100), 'intl_localized_decimal'));
    }

    /**
     * @requires extension intl
     */
    public function testMoneyIsFormattedAsIntlMoney(): void
    {
        $this->assertSame('$1.00', $this->extension->formatMoney(Money::USD(100), 'intl_money'));
    }

    /**
     * @requires extension intl
     */
    public function testMoneyIsFormattedAsIntlMoneyWithCustomOptions(): void
    {
        $this->assertSame('1', $this->extension->formatMoney(Money::EUR(100), 'intl_money', 'en', ['fraction_digits' => 0, 'style' => MoneyExtension::STYLE_DECIMAL]));
    }

    public function testMoneyIsNotFormattedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported", allowed formats: [bitcoin, decimal, intl_localized_decimal, intl_money]');

        $this->assertSame('1', $this->extension->formatMoney(Money::EUR(100), 'unsupported'));
    }
}
