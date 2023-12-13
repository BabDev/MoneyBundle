<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Form\DataTransformer;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;
use BabDev\MoneyBundle\Format;
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
    public function __construct(
        private FormatterFactoryInterface $formatterFactory,
        private ParserFactoryInterface $parserFactory,
        private Currency $currency,
        private NumberToLocalizedStringTransformer $numberTransformer,
        private ?string $locale = null,
    ) {}

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
