<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Formatter;

use Money\Currencies;
use Money\Money;
use Money\MoneyFormatter;

/**
 * Formats a {@see Money} instance with the number of fraction digits used by its currency.
 *
 * The decorated formatter must use the given {@see \NumberFormatter}, whose fraction digits are set for each formatted amount.
 *
 * @internal
 */
final readonly class CurrencyFractionDigitsFormatter implements MoneyFormatter
{
    public function __construct(
        private MoneyFormatter $formatter,
        private \NumberFormatter $numberFormatter,
        private Currencies $currencies,
    ) {}

    public function format(Money $money): string
    {
        $this->numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->currencies->subunitFor($money->getCurrency()));

        return $this->formatter->format($money);
    }
}
