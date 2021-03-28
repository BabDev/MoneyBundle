<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use Money\MoneyParser;

interface ParserFactoryInterface
{
    public const STYLE_CURRENCY = 'currency';
    public const STYLE_DECIMAL = 'decimal';

    /**
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a parser is not available
     */
    public function createParser(string $format, ?string $locale, array $options): MoneyParser;
}
