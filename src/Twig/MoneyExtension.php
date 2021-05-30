<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Twig;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Format;
use Money\Currency;
use Money\Money;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class MoneyExtension extends AbstractExtension
{
    private FormatterFactoryInterface $formatterFactory;
    private string $defaultCurrency;

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
     * @phpstan-param numeric-string|int $amount
     */
    public function createMoney($amount, ?string $currency = null): Money
    {
        return new Money($amount, new Currency($currency ?: $this->defaultCurrency));
    }

    /**
     * @phpstan-param Format::* $format
     */
    public function formatMoney(Money $money, string $format = 'intl_money', ?string $locale = null, array $options = []): string
    {
        return $this->formatterFactory->createFormatter($format, $locale, $options)->format($money);
    }
}
