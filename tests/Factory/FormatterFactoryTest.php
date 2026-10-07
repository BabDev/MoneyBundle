<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Factory;

use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Formatter\CurrencyFractionDigitsFormatter;
use Money\Currencies\CurrencyList;
use Money\Currency;
use Money\Formatter\AggregateMoneyFormatter;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleAwareListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

    #[RequiresPhpExtension('intl')]
    public function testIntlFormattersUseTheFactoryLocaleWhenNoLocaleIsGiven(): void
    {
        self::assertSame('en_US', $this->factory->getLocale());

        $this->factory->setLocale('de_DE');

        self::assertSame('de_DE', $this->factory->getLocale());
        self::assertSame('1.234,50', $this->factory->createFormatter(Format::INTL_MONEY, null, ['style' => 'decimal'])->format(Money::EUR(123450)));
        self::assertSame('1,234.50', $this->factory->createFormatter(Format::INTL_MONEY, 'en_US', ['style' => 'decimal'])->format(Money::EUR(123450)));
    }

    #[RequiresPhpExtension('intl')]
    public function testFactoryLocaleFollowsTheRequestLocale(): void
    {
        $requestStack = new RequestStack();
        $listener = new LocaleAwareListener([$this->factory], $requestStack);
        $kernel = self::createStub(HttpKernelInterface::class);

        $format = fn (): string => $this->factory->createFormatter(Format::INTL_MONEY, null, ['style' => 'decimal'])->format(Money::EUR(123450));

        $mainRequest = Request::create('/');
        $mainRequest->setDefaultLocale('en_US');
        $mainRequest->setLocale('de_DE');
        $requestStack->push($mainRequest);
        $listener->onKernelRequest(new RequestEvent($kernel, $mainRequest, HttpKernelInterface::MAIN_REQUEST));

        self::assertSame('1.234,50', $format());

        $subRequest = Request::create('/');
        $subRequest->setDefaultLocale('en_US');
        $subRequest->setLocale('fr_FR');
        $requestStack->push($subRequest);
        $listener->onKernelRequest(new RequestEvent($kernel, $subRequest, HttpKernelInterface::SUB_REQUEST));

        // The French grouping separator differs between ICU versions
        $frenchFormatter = new \NumberFormatter('fr_FR', \NumberFormatter::DECIMAL);
        $frenchFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);

        self::assertSame($frenchFormatter->format(1234.5), $format());

        $listener->onKernelFinishRequest(new FinishRequestEvent($kernel, $subRequest, HttpKernelInterface::SUB_REQUEST));
        $requestStack->pop();

        self::assertSame('1.234,50', $format());

        $listener->onKernelFinishRequest(new FinishRequestEvent($kernel, $mainRequest, HttpKernelInterface::MAIN_REQUEST));
        $requestStack->pop();

        self::assertSame('1,234.50', $format());
    }

    #[RequiresPhpExtension('intl')]
    public function testFormattersUseTheGivenCurrencies(): void
    {
        $factory = new FormatterFactory('en_US', new CurrencyList(['PTS' => 3]));
        $money = new Money(123456, new Currency('PTS'));

        self::assertSame('123.456', $factory->createFormatter(Format::DECIMAL)->format($money));
        self::assertSame('123.456', $factory->createFormatter(Format::INTL_MONEY, null, ['style' => 'decimal'])->format($money));
        self::assertSame('123.456', $factory->createFormatter(Format::INTL_LOCALIZED_DECIMAL, null, ['style' => 'decimal'])->format($money));
    }

    #[RequiresPhpExtension('intl')]
    public function testFormattersAreReusedForTheSameArguments(): void
    {
        $formatter = $this->factory->createFormatter(Format::INTL_MONEY, null, ['style' => 'decimal']);

        self::assertSame($formatter, $this->factory->createFormatter(Format::INTL_MONEY, null, ['style' => 'decimal']));
        self::assertSame($formatter, $this->factory->createFormatter(Format::INTL_MONEY, 'en_US', ['style' => 'decimal']));
        self::assertNotSame($formatter, $this->factory->createFormatter(Format::INTL_MONEY, 'de_DE', ['style' => 'decimal']));
        self::assertNotSame($formatter, $this->factory->createFormatter(Format::INTL_MONEY));
        self::assertNotSame($formatter, $this->factory->createFormatter(Format::INTL_LOCALIZED_DECIMAL, null, ['style' => 'decimal']));

        $this->factory->setLocale('de_DE');

        self::assertNotSame($formatter, $this->factory->createFormatter(Format::INTL_MONEY, null, ['style' => 'decimal']));
    }

    public function testFormatterIsNotCreatedWhenAnUnsupportedFormatIsGiven(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported format "unsupported"');

        $this->factory->createFormatter('unsupported'); // @phpstan-ignore-line argument.type
    }
}
