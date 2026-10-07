<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Format;

/**
 * Creates the {@see \NumberFormatter} used by the intl formatters and parsers.
 *
 * @internal
 */
trait CreatesNumberFormatters
{
    /**
     * @param array{grouping_used?: bool, style?: string} $options
     *
     * @phpstan-param Format::INTL_* $format
     *
     * @throws MissingDependencyException if the intl extension or its polyfill is not available
     */
    private function createNumberFormatter(string $format, string $locale, array $options): \NumberFormatter
    {
        if (!class_exists(\NumberFormatter::class)) {
            throw new MissingDependencyException(\sprintf('The "%s" format requires the "%s" class to be available. You will need to either install the PHP "intl" extension or the "symfony/polyfill-intl-icu" package with Composer (the polyfill is only available for the "en" locale).', $format, \NumberFormatter::class));
        }

        $style = FormatterFactoryInterface::STYLE_DECIMAL === ($options['style'] ?? FormatterFactoryInterface::STYLE_CURRENCY) ? \NumberFormatter::DECIMAL : \NumberFormatter::CURRENCY;

        $numberFormatter = new \NumberFormatter($locale, $style);
        $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, ($options['grouping_used'] ?? true) ? 1 : 0);

        return $numberFormatter;
    }
}
