<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Format;
use Money\Currencies;
use Money\Currencies\ISOCurrencies;
use Money\MoneyParser;
use Money\Parser\AggregateMoneyParser;
use Money\Parser\BitcoinMoneyParser;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlLocalizedDecimalParser;
use Money\Parser\IntlMoneyParser;

final class ParserFactory implements ParserFactoryInterface
{
    use CreatesNumberFormatters;

    /**
     * @var array<string, class-string<MoneyParser>>
     *
     * @phpstan-var array<Format::*, class-string<MoneyParser>>
     */
    private const array PARSER_MAP = [
        Format::BITCOIN => BitcoinMoneyParser::class,
        Format::DECIMAL => DecimalMoneyParser::class,
        Format::INTL_LOCALIZED_DECIMAL => IntlLocalizedDecimalParser::class,
        Format::INTL_MONEY => IntlMoneyParser::class,
    ];

    public function __construct(
        private readonly string $defaultLocale,
        private readonly Currencies $currencies = new ISOCurrencies(),
    ) {}

    /**
     * @param non-empty-string                                                                     $format
     * @param array{fraction_digits?: int<0, max>|null, grouping_used?: bool, style?: string|null} $options
     *
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a parser is not available
     */
    public function createParser(string $format, ?string $locale = null, array $options = []): MoneyParser
    {
        switch ($format) {
            case Format::AGGREGATE:
                throw new UnsupportedFormatException(array_keys(self::PARSER_MAP), \sprintf('The "%s" class is not supported by "%s".', AggregateMoneyParser::class, self::class));
            case Format::BITCOIN:
                $fractionDigits = (int) ($options['fraction_digits'] ?? 8);

                return new BitcoinMoneyParser($fractionDigits);

            case Format::DECIMAL:
                return new DecimalMoneyParser($this->currencies);

            case Format::INTL_LOCALIZED_DECIMAL:
            case Format::INTL_MONEY:
                $numberFormatter = $this->createNumberFormatter($format, $locale ?: $this->defaultLocale, $options);
                $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) ($options['fraction_digits'] ?? 2));

                return Format::INTL_MONEY === $format
                    ? new IntlMoneyParser($numberFormatter, $this->currencies)
                    : new IntlLocalizedDecimalParser($numberFormatter, $this->currencies);

            default:
                throw new UnsupportedFormatException(array_keys(self::PARSER_MAP), \sprintf('Unsupported format "%s"', $format));
        }
    }
}
