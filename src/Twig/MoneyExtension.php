<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Twig;

use BabDev\MoneyBundle\Factory\Exception\MissingDependencyException;
use BabDev\MoneyBundle\Factory\Exception\UnsupportedFormatException;
use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Format;
use Money\Currency;
use Money\Exception\FormatterException;
use Money\Money;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class MoneyExtension extends AbstractExtension
{
    private FormatterFactoryInterface $formatterFactory;

    /**
     * @phpstan-var non-empty-string
     */
    private string $defaultCurrency;

    /**
     * @phpstan-param non-empty-string $defaultCurrency
     */
    public function __construct(FormatterFactoryInterface $formatterFactory, string $defaultCurrency)
    {
        $this->formatterFactory = $formatterFactory;
        $this->defaultCurrency = $defaultCurrency;
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

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('money', [$this, 'createMoney']),
        ];
    }

    /**
     * @param string|int $amount
     *
     * @phpstan-param numeric-string|int    $amount
     * @phpstan-param non-empty-string|null $currency
     *
     * @throws \InvalidArgumentException if the amount cannot be converted to a {@see Money} instance
     */
    public function createMoney($amount, ?string $currency = null): Money
    {
        return new Money($amount, new Currency($currency ?: $this->defaultCurrency));
    }

    /**
     * @phpstan-param Format::* $format
     *
     * @throws UnsupportedFormatException if an unsupported format was requested
     * @throws MissingDependencyException if a dependency for a formatter is not available
     * @throws FormatterException         if the {@see Money} instance cannot be formatted
     */
    public function formatMoney(Money $money, string $format = Format::INTL_MONEY, ?string $locale = null, array $options = []): string
    {
        return $this->formatterFactory->createFormatter($format, $locale, $options)->format($money);
    }
}
