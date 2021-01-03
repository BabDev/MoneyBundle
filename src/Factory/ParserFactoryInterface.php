<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use Money\MoneyParser;

interface ParserFactoryInterface
{
    public const STYLE_CURRENCY = 'currency';
    public const STYLE_DECIMAL = 'decimal';

    /**
     * @throws \InvalidArgumentException if an unsupported format was requested
     * @throws \RuntimeException         if a dependency for a parser is not available
     */
    public function createParser(string $format, ?string $locale, array $options): MoneyParser;
}
