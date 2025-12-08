<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Factory;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Format;
use Money\MoneyFormatter;

interface FormatterFactoryInterface
{
    public const string STYLE_CURRENCY = 'currency';
    public const string STYLE_DECIMAL = 'decimal';

    /**
     * @phpstan-param Format::* $format
     * @phpstan-param array<string, mixed> $options
     *
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a formatter is not available
     */
    public function createFormatter(string $format, ?string $locale, array $options): MoneyFormatter;
}
