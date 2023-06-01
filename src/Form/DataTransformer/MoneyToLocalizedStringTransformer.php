<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Form\DataTransformer;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use BabDev\MoneyBundle\Format;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

/**
 * Transforms between a normalized format and a localized money string.
 *
 * Class is based on \Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer
 *
 * @template T of Money
 * @template R of string
 *
 * @implements DataTransformerInterface<T, R>
 */
final class MoneyToLocalizedStringTransformer implements DataTransformerInterface
{
    private FormatterFactoryInterface $formatterFactory;
    private ParserFactoryInterface $parserFactory;
    private Currency $currency;
    private ?string $locale;
    private NumberToLocalizedStringTransformer $numberTransformer;

    /**
     * @param NumberToLocalizedStringTransformer|int|null $scaleOrTransformer
     *
     * @throws InvalidArgumentException if an invalid constructor parameter is provided
     */
    public function __construct(FormatterFactoryInterface $formatterFactory, ParserFactoryInterface $parserFactory, Currency $currency, $scaleOrTransformer = 2, ?bool $grouping = true, ?int $roundingMode = \NumberFormatter::ROUND_HALFUP, ?string $locale = null)
    {
        if ($scaleOrTransformer instanceof NumberToLocalizedStringTransformer) {
            $this->numberTransformer = $scaleOrTransformer;
        } elseif (\is_int($scaleOrTransformer) || null === $scaleOrTransformer) {
            trigger_deprecation('babdev/money-bundle', '1.5', 'Passing an integer or null as the fourth argument to the "%s" constructor is deprecated. In 2.0, a "%s" instance will be required.', self::class, NumberToLocalizedStringTransformer::class);

            $this->numberTransformer = new NumberToLocalizedStringTransformer($scaleOrTransformer, $grouping, $roundingMode, $locale);
        } else {
            throw new InvalidArgumentException(sprintf('The fourth argument to the %s constructor must be an instance of %s, an integer, or null; %s given', self::class, NumberToLocalizedStringTransformer::class, get_debug_type($scaleOrTransformer)));
        }

        $this->formatterFactory = $formatterFactory;
        $this->parserFactory = $parserFactory;
        $this->currency = $currency;
        $this->locale = $locale;
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

        if (!($value instanceof Money)) {
            throw new TransformationFailedException(sprintf('Expected an instance of "%s", "%s" given.', Money::class, get_debug_type($value)));
        }

        $formatter = $this->formatterFactory->createFormatter(Format::DECIMAL, $this->locale, []);

        return $this->numberTransformer->transform((float) $formatter->format($value));
    }

    /**
     * @param string $value Localized money string
     *
     * @phpstan-return Money|null
     *
     * @throws TransformationFailedException if the given value is not a string or if the value can not be transformed
     */
    public function reverseTransform($value): ?Money
    {
        $value = $this->numberTransformer->reverseTransform($value);

        if (null === $value) {
            return null;
        }

        $parser = $this->parserFactory->createParser(Format::DECIMAL, $this->locale, []);

        try {
            return $parser->parse(sprintf('%.53f', $value), $this->currency);
        } catch (ParserException $e) {
            throw new TransformationFailedException($e->getMessage(), 0, $e);
        }
    }
}
