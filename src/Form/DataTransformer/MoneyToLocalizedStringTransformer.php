<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Form\DataTransformer;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use BabDev\MoneyBundle\Format;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

/**
 * Transforms between a normalized format and a localized money string.
 *
 * Class is based on {@see \Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer}
 *
 * @template T of Money
 * @template R of string
 *
 * @implements DataTransformerInterface<T, R>
 */
final readonly class MoneyToLocalizedStringTransformer implements DataTransformerInterface
{
    /**
     * The number of significant digits a float represents exactly.
     */
    private const int FLOAT_DIGITS = 15;

    private int $subunit;

    private int $scale;

    /**
     * The number of minor unit digits removed when rounding an amount to the scale.
     *
     * @var int<0, max>
     */
    private int $roundingUnit;

    /**
     * @param string|null $locale       The locale used by the number transformer, used to localize amounts which cannot be converted through it precisely
     * @param int|null    $scale        The number of decimal places for the amount, which must be the same as the number transformer's; defaults to the currency's subunit
     * @param int         $roundingMode The rounding mode for the amount, as one of the NumberFormatter::ROUND_* constants, which must be the same as the number transformer's
     *
     * @throws \InvalidArgumentException if the currency is not supported or the scale is negative or greater than the currency's subunit
     */
    public function __construct(
        private FormatterFactoryInterface $formatterFactory,
        private ParserFactoryInterface $parserFactory,
        private Currency $currency,
        private NumberToLocalizedStringTransformer $numberTransformer,
        private ?string $locale = null,
        ?int $scale = null,
        private int $roundingMode = \NumberFormatter::ROUND_HALFUP,
    ) {
        $currencies = new ISOCurrencies();

        if (!$currencies->contains($currency)) {
            throw new \InvalidArgumentException(\sprintf('The "%s" currency is not supported.', $currency->getCode()));
        }

        $subunit = $currencies->subunitFor($currency);
        $scale ??= $subunit;
        $roundingUnit = $subunit - $scale;

        if ($scale < 0 || $roundingUnit < 0) {
            throw new \InvalidArgumentException(\sprintf('The scale must be between 0 and the number of decimal places used by the "%s" currency (%d), %d given.', $currency->getCode(), $subunit, $scale));
        }

        $this->subunit = $subunit;
        $this->scale = $scale;
        $this->roundingUnit = $roundingUnit;
    }

    /**
     * @param Money|null $value Money object
     *
     * @return string Localized money string
     *
     * @throws TransformationFailedException if the given value is not a Money instance or if the value can not be transformed
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof Money) {
            throw new TransformationFailedException(\sprintf('Expected an instance of "%s", "%s" given.', Money::class, get_debug_type($value)));
        }

        if (!$this->isPrecise($value)) {
            return $this->transformPrecisely($value);
        }

        $formatter = $this->formatterFactory->createFormatter(Format::DECIMAL, $this->locale, []);

        return $this->numberTransformer->transform((float) $formatter->format($value));
    }

    /**
     * @param string $value Localized money string
     *
     * @return Money|null Normalized money object
     *
     * @throws TransformationFailedException if the given value is not a string or if the value can not be transformed
     */
    public function reverseTransform($value): ?Money
    {
        $value = $this->numberTransformer->reverseTransform($value);

        if (null === $value) {
            return null;
        }

        $maxPreciseDigits = $this->getMaxPreciseDigits();

        if (abs($value) >= 10 ** ($maxPreciseDigits - $this->scale)) {
            throw new TransformationFailedException(\sprintf('The amount has more than %d significant digits and cannot be converted precisely.', $maxPreciseDigits));
        }

        $parser = $this->parserFactory->createParser(Format::DECIMAL, $this->locale, []);

        try {
            return $parser->parse(number_format($value, $this->scale, '.', ''), $this->currency);
        } catch (ParserException $e) {
            throw new TransformationFailedException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Checks if the amount, rounded to the scale, can be converted through a float without losing precision.
     */
    private function isPrecise(Money $value): bool
    {
        $digits = \strlen(ltrim($value->getAmount(), '-0')) - $this->roundingUnit;

        return $digits <= $this->getMaxPreciseDigits();
    }

    /**
     * Gets the number of significant digits which can be converted through the float based number transformer without losing precision.
     *
     * The number transformer rounds submitted values by casting them to a string, which is limited to PHP's "precision" setting (14 by default, or the shortest exact representation when set to -1).
     */
    private function getMaxPreciseDigits(): int
    {
        $precision = (int) \ini_get('precision');

        if (-1 === $precision) {
            return self::FLOAT_DIGITS;
        }

        return max(1, min($precision, self::FLOAT_DIGITS));
    }

    /**
     * Localizes the amount without converting it through a float.
     *
     * Only the decimal separator and minus sign are localized; grouping is not applied.
     */
    private function transformPrecisely(Money $value): string
    {
        $amount = $value->roundToUnit($this->roundingUnit, $this->getMoneyRoundingMode($value))->getAmount();

        $negative = str_starts_with($amount, '-');
        $digits = str_pad(ltrim($amount, '-'), $this->subunit + 1, '0', \STR_PAD_LEFT);

        $integer = 0 === $this->subunit ? $digits : substr($digits, 0, -$this->subunit);
        $fraction = 0 === $this->subunit ? '' : substr($digits, -$this->subunit, $this->scale);

        $numberFormatter = new \NumberFormatter($this->locale ?? \Locale::getDefault(), \NumberFormatter::DECIMAL);

        $localized = $integer;

        if ('' !== $fraction) {
            $localized .= $numberFormatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL).$fraction;
        }

        return $negative ? $numberFormatter->getSymbol(\NumberFormatter::MINUS_SIGN_SYMBOL).$localized : $localized;
    }

    /**
     * Maps the NumberFormatter rounding mode to its equivalent {@see Money} rounding mode, where the Money "up" and "down" modes round toward positive and negative infinity.
     *
     * @return Money::ROUND_*
     */
    private function getMoneyRoundingMode(Money $value): int
    {
        return match ($this->roundingMode) {
            \NumberFormatter::ROUND_CEILING => Money::ROUND_UP,
            \NumberFormatter::ROUND_FLOOR => Money::ROUND_DOWN,
            \NumberFormatter::ROUND_UP => $value->isNegative() ? Money::ROUND_DOWN : Money::ROUND_UP,
            \NumberFormatter::ROUND_DOWN => $value->isNegative() ? Money::ROUND_UP : Money::ROUND_DOWN,
            \NumberFormatter::ROUND_HALFDOWN => Money::ROUND_HALF_DOWN,
            \NumberFormatter::ROUND_HALFEVEN => Money::ROUND_HALF_EVEN,
            default => Money::ROUND_HALF_UP,
        };
    }
}
