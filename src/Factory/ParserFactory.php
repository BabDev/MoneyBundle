<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Format;
use Money\Currencies\ISOCurrencies;
use Money\MoneyParser;
use Money\Parser\AggregateMoneyParser;
use Money\Parser\BitcoinMoneyParser;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Money\Parser\IntlMoneyParser;

final class ParserFactory implements ParserFactoryInterface
{
    /**
     * @var array<string, class-string<MoneyParser>>
     * @phpstan-var array<Format::*, class-string<MoneyParser>>
     */
    private const PARSER_MAP = [
        Format::BITCOIN => BitcoinMoneyParser::class,
        Format::DECIMAL => DecimalMoneyParser::class,
        Format::INTL_LOCALIZED_DECIMAL => IntlLocalizedDecimalParser::class,
        Format::INTL_MONEY => IntlMoneyParser::class,
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
     * @throws MissingDependencyException if a dependency for a parser is not available
     */
    public function createParser(string $format, ?string $locale = null, array $options = []): MoneyParser
    {
        switch ($format) {
            case Format::AGGREGATE:
                throw new UnsupportedFormatException(array_keys(self::PARSER_MAP), sprintf('The "%s" class is not supported by "%s".', AggregateMoneyParser::class, self::class));
            case Format::BITCOIN:
                $fractionDigits = (int) ($options['fraction_digits'] ?? 8);

                return new BitcoinMoneyParser($fractionDigits);

            case Format::DECIMAL:
                return new DecimalMoneyParser(new ISOCurrencies());

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

                return new IntlLocalizedDecimalParser($numberFormatter, new ISOCurrencies());

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

                return new IntlMoneyParser($numberFormatter, new ISOCurrencies());

            default:
                throw new UnsupportedFormatException(array_keys(self::PARSER_MAP), sprintf('Unsupported format "%s"', $format));
        }
    }
}
