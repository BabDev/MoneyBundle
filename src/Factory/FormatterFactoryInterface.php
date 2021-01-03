<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use Money\MoneyFormatter;

interface FormatterFactoryInterface
{
    public const STYLE_CURRENCY = 'currency';
    public const STYLE_DECIMAL = 'decimal';

    /**
     * @throws \InvalidArgumentException if an unsupported format was requested
     * @throws \RuntimeException         if a dependency for a formatter is not available
     */
    public function createFormatter(string $format, ?string $locale, array $options): MoneyFormatter;
}
