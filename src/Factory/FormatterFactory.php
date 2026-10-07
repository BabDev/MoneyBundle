<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Format;
use BabDev\MoneyBundle\Formatter\CurrencyFractionDigitsFormatter;
use Money\Currencies;
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
    use CreatesNumberFormatters;

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

    /**
     * @var array<string, MoneyFormatter>
     */
    private array $formatters = [];

    public function __construct(
        string $defaultLocale,
        private readonly Currencies $currencies = new ISOCurrencies(),
    ) {
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
     * @param non-empty-string                                                                     $format
     * @param array{fraction_digits?: int<0, max>|null, grouping_used?: bool, style?: string|null} $options
     *
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a formatter is not available
     */
    public function createFormatter(string $format, ?string $locale = null, array $options = []): MoneyFormatter
    {
        // Formatters are reused for the same arguments, as creating the intl formatters is relatively expensive and the formatters do not keep any state between calls
        $key = serialize([$format, $locale ?: $this->locale, $options['fraction_digits'] ?? null, $options['grouping_used'] ?? true, $options['style'] ?? null]);

        return $this->formatters[$key] ??= $this->doCreateFormatter($format, $locale, $options);
    }

    /**
     * @param array{fraction_digits?: int<0, max>|null, grouping_used?: bool, style?: string|null} $options
     *
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a formatter is not available
     */
    private function doCreateFormatter(string $format, ?string $locale, array $options): MoneyFormatter
    {
        switch ($format) {
            case Format::AGGREGATE:
                throw new UnsupportedFormatException(array_keys(self::FORMAT_MAP), \sprintf('The "%s" class is not supported by "%s".', AggregateMoneyFormatter::class, self::class));
            case Format::BITCOIN:
                $fractionDigits = (int) ($options['fraction_digits'] ?? 8);

                return new BitcoinMoneyFormatter($fractionDigits, new BitcoinCurrencies());

            case Format::DECIMAL:
                return new DecimalMoneyFormatter($this->currencies);

            case Format::INTL_LOCALIZED_DECIMAL:
            case Format::INTL_MONEY:
                $numberFormatter = $this->createNumberFormatter($format, $locale ?: $this->locale, $options);

                $formatter = Format::INTL_MONEY === $format
                    ? new IntlMoneyFormatter($numberFormatter, $this->currencies)
                    : new IntlLocalizedDecimalFormatter($numberFormatter, $this->currencies);

                if (!isset($options['fraction_digits'])) {
                    return new CurrencyFractionDigitsFormatter($formatter, $numberFormatter, $this->currencies);
                }

                $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) $options['fraction_digits']);

                return $formatter;

            default:
                throw new UnsupportedFormatException(array_keys(self::FORMAT_MAP), \sprintf('Unsupported format "%s"', $format));
        }
    }
}
