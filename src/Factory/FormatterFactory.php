<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Format;
use Money\Currencies\BitcoinCurrencies;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\MoneyFormatter;

final class FormatterFactory implements FormatterFactoryInterface
{
    /**
     * @var array<string, class-string>
     */
    private const FORMAT_MAP = [
        Format::BITCOIN => BitcoinMoneyFormatter::class,
        Format::DECIMAL => DecimalMoneyFormatter::class,
        Format::INTL_LOCALIZED_DECIMAL => IntlLocalizedDecimalFormatter::class,
        Format::INTL_MONEY => IntlMoneyFormatter::class,
    ];

    private string $defaultLocale;

    public function __construct(string $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @phpstan-param Format::* $format
     *
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a formatter is not available
     */
    public function createFormatter(string $format, ?string $locale = null, array $options = []): MoneyFormatter
    {
        switch ($format) {
            case Format::BITCOIN:
                $fractionDigits = (int) ($options['fraction_digits'] ?? 8);

                return new BitcoinMoneyFormatter($fractionDigits, new BitcoinCurrencies());

            case Format::DECIMAL:
                return new DecimalMoneyFormatter(new ISOCurrencies());

            case Format::INTL_LOCALIZED_DECIMAL:
                if (!class_exists(\NumberFormatter::class)) {
                    throw new MissingDependencyException(sprintf('The "intl_localized_decimal" format requires the "%s" class to be available. You will need to either install the PHP "intl" extension or the "symfony/polyfill-intl-icu" package with Composer (the polyfill is only available for the "en" locale).', \NumberFormatter::class));
                }

                $formatterLocale = $locale ?: $this->defaultLocale;
                $fractionDigits = (int) ($options['fraction_digits'] ?? 2);
                $groupingUsed = (bool) ($options['grouping_used'] ?? true);
                $optionsStyle = $options['style'] ?? self::STYLE_CURRENCY;

                $numberFormatter = new \NumberFormatter($formatterLocale, self::STYLE_DECIMAL === $optionsStyle ? \NumberFormatter::DECIMAL : \NumberFormatter::CURRENCY);
                $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fractionDigits);
                $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $groupingUsed ? 1 : 0);

                return new IntlLocalizedDecimalFormatter($numberFormatter, new ISOCurrencies());

            case Format::INTL_MONEY:
                if (!class_exists(\NumberFormatter::class)) {
                    throw new MissingDependencyException(sprintf('The "intl_money" format requires the "%s" class to be available. You will need to either install the PHP "intl" extension or the "symfony/polyfill-intl-icu" package with Composer (the polyfill is only available for the "en" locale).', \NumberFormatter::class));
                }

                $formatterLocale = $locale ?: $this->defaultLocale;
                $fractionDigits = (int) ($options['fraction_digits'] ?? 2);
                $groupingUsed = (bool) ($options['grouping_used'] ?? true);
                $optionsStyle = $options['style'] ?? self::STYLE_CURRENCY;

                $numberFormatter = new \NumberFormatter($formatterLocale, self::STYLE_DECIMAL === $optionsStyle ? \NumberFormatter::DECIMAL : \NumberFormatter::CURRENCY);
                $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fractionDigits);
                $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $groupingUsed ? 1 : 0);

                return new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

            default:
                throw new UnsupportedFormatException(array_keys(self::FORMAT_MAP), sprintf('Unsupported format "%s"', $format));
        }
    }
}
