<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Formatter\CurrencyFractionDigitsFormatter;
use Money\Currencies\BitcoinCurrencies;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\AggregateMoneyFormatter;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\MoneyFormatter;
use Symfony\Contracts\Translation\LocaleAwareInterface;

final class FormatterFactory implements FormatterFactoryInterface, LocaleAwareInterface
{
    /**
     * @var array<string, class-string<MoneyFormatter>>
     *
     * @phpstan-var array<Format::*, class-string<MoneyFormatter>>
     */
    private const array FORMAT_MAP = [
        Format::BITCOIN => BitcoinMoneyFormatter::class,
        Format::DECIMAL => DecimalMoneyFormatter::class,
        Format::INTL_LOCALIZED_DECIMAL => IntlLocalizedDecimalFormatter::class,
        Format::INTL_MONEY => IntlMoneyFormatter::class,
    ];

    /**
     * The locale used by the intl formatters when no locale is given, which follows the current request's locale when available.
     */
    private string $locale;

    public function __construct(string $defaultLocale)
    {
        $this->locale = $defaultLocale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param array{fraction_digits?: int<0, max>|null, grouping_used?: bool, style?: string} $options
     *
     * @phpstan-param Format::* $format
     *
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a formatter is not available
     */
    public function createFormatter(string $format, ?string $locale = null, array $options = []): MoneyFormatter
    {
        switch ($format) {
            case Format::AGGREGATE:
                throw new UnsupportedFormatException(array_keys(self::FORMAT_MAP), \sprintf('The "%s" class is not supported by "%s".', AggregateMoneyFormatter::class, self::class));
            case Format::BITCOIN:
                $fractionDigits = (int) ($options['fraction_digits'] ?? 8);

                return new BitcoinMoneyFormatter($fractionDigits, new BitcoinCurrencies());

            case Format::DECIMAL:
                return new DecimalMoneyFormatter(new ISOCurrencies());

            case Format::INTL_LOCALIZED_DECIMAL:
                if (!class_exists(\NumberFormatter::class)) {
                    throw new MissingDependencyException(\sprintf('The "intl_localized_decimal" format requires the "%s" class to be available. You will need to either install the PHP "intl" extension or the "symfony/polyfill-intl-icu" package with Composer (the polyfill is only available for the "en" locale).', \NumberFormatter::class));
                }

                $formatterLocale = $locale ?: $this->locale;
                $groupingUsed = (bool) ($options['grouping_used'] ?? true);
                $optionsStyle = $options['style'] ?? self::STYLE_CURRENCY;

                $numberFormatter = new \NumberFormatter($formatterLocale, self::STYLE_DECIMAL === $optionsStyle ? \NumberFormatter::DECIMAL : \NumberFormatter::CURRENCY);
                $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $groupingUsed ? 1 : 0);

                $currencies = new ISOCurrencies();
                $formatter = new IntlLocalizedDecimalFormatter($numberFormatter, $currencies);

                if (!isset($options['fraction_digits'])) {
                    return new CurrencyFractionDigitsFormatter($formatter, $numberFormatter, $currencies);
                }

                $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) $options['fraction_digits']);

                return $formatter;

            case Format::INTL_MONEY:
                if (!class_exists(\NumberFormatter::class)) {
                    throw new MissingDependencyException(\sprintf('The "intl_money" format requires the "%s" class to be available. You will need to either install the PHP "intl" extension or the "symfony/polyfill-intl-icu" package with Composer (the polyfill is only available for the "en" locale).', \NumberFormatter::class));
                }

                $formatterLocale = $locale ?: $this->locale;
                $groupingUsed = (bool) ($options['grouping_used'] ?? true);
                $optionsStyle = $options['style'] ?? self::STYLE_CURRENCY;

                $numberFormatter = new \NumberFormatter($formatterLocale, self::STYLE_DECIMAL === $optionsStyle ? \NumberFormatter::DECIMAL : \NumberFormatter::CURRENCY);
                $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $groupingUsed ? 1 : 0);

                $currencies = new ISOCurrencies();
                $formatter = new IntlMoneyFormatter($numberFormatter, $currencies);

                if (!isset($options['fraction_digits'])) {
                    return new CurrencyFractionDigitsFormatter($formatter, $numberFormatter, $currencies);
                }

                $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) $options['fraction_digits']);

                return $formatter;

            default:
                throw new UnsupportedFormatException(array_keys(self::FORMAT_MAP), \sprintf('Unsupported format "%s"', $format));
        }
    }
}
