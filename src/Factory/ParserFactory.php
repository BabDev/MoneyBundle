<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use Money\Currencies\ISOCurrencies;
use Money\MoneyParser;
use Money\Parser\BitcoinMoneyParser;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Money\Parser\IntlMoneyParser;

final class ParserFactory implements ParserFactoryInterface
{
    /**
     * @var array<string, class-string>
     */
    private const PARSER_MAP = [
        'bitcoin' => BitcoinMoneyParser::class,
        'decimal' => DecimalMoneyParser::class,
        'intl_localized_decimal' => IntlLocalizedDecimalParser::class,
        'intl_money' => IntlMoneyParser::class,
    ];

    private string $defaultLocale;

    public function __construct(string $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a parser is not available
     */
    public function createParser(string $format, ?string $locale = null, array $options = []): MoneyParser
    {
        switch ($format) {
            case 'bitcoin':
                $fractionDigits = (int) ($options['fraction_digits'] ?? 8);

                return new BitcoinMoneyParser($fractionDigits);

            case 'decimal':
                return new DecimalMoneyParser(new ISOCurrencies());

            case 'intl_localized_decimal':
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

            case 'intl_money':
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
