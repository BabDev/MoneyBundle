<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Twig;

use Money\Currencies\BitcoinCurrencies;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\BitcoinMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlLocalizedDecimalFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MoneyExtension extends AbstractExtension
{
    public const STYLE_CURRENCY = 'currency';
    public const STYLE_DECIMAL = 'decimal';

    /**
     * @var array<string, class-string>
     */
    private const FORMAT_MAP = [
        'bitcoin' => BitcoinMoneyFormatter::class,
        'decimal' => DecimalMoneyFormatter::class,
        'intl_localized_decimal' => IntlLocalizedDecimalFormatter::class,
        'intl_money' => IntlMoneyFormatter::class,
    ];

    private string $defaultLocale;

    public function __construct(string $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('money', [$this, 'formatMoney']),
        ];
    }

    public function formatMoney(Money $money, string $format = 'intl_money', ?string $locale = null, array $options = []): string
    {
        return $this->createFormatter($format, $locale, $options)->format($money);
    }

    private function createFormatter(string $format, ?string $locale, array $options): MoneyFormatter
    {
        switch ($format) {
            case 'bitcoin':
                $fractionDigits = (int) ($options['fraction_digits'] ?? 8);

                return new BitcoinMoneyFormatter($fractionDigits, new BitcoinCurrencies());

            case 'decimal':
                return new DecimalMoneyFormatter(new ISOCurrencies());

            case 'intl_localized_decimal':
                if (!class_exists(\NumberFormatter::class)) {
                    throw new \RuntimeException(sprintf('The "intl_localized_decimal" format requires the "%s" class to be available. You will need to either install the PHP "intl" extension or the "symfony/polyfill-intl-icu" package with Composer (the polyfill is only available for the "en" locale).', \NumberFormatter::class));
                }

                $formatterLocale = $locale ?: $this->defaultLocale;
                $fractionDigits = (int) ($options['fraction_digits'] ?? 2);
                $groupingUsed = (bool) ($options['grouping_used'] ?? true);
                $optionsStyle = $options['style'] ?? self::STYLE_CURRENCY;

                $numberFormatter = new \NumberFormatter($formatterLocale, self::STYLE_DECIMAL === $optionsStyle ? \NumberFormatter::DECIMAL : \NumberFormatter::CURRENCY);
                $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fractionDigits);
                $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $fractionDigits);
                $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $groupingUsed ? 1 : 0);

                return new IntlLocalizedDecimalFormatter($numberFormatter, new ISOCurrencies());

            case 'intl_money':
                if (!class_exists(\NumberFormatter::class)) {
                    throw new \RuntimeException(sprintf('The "intl_money" format requires the "%s" class to be available. You will need to either install the PHP "intl" extension or the "symfony/polyfill-intl-icu" package with Composer (the polyfill is only available for the "en" locale).', \NumberFormatter::class));
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
                throw new \InvalidArgumentException(sprintf('Unsupported format "%s", allowed formats: [%s]', $format, implode(', ', array_keys(self::FORMAT_MAP))));
        }
    }
}
