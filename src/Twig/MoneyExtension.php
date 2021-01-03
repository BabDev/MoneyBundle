<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Twig;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use Money\Money;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MoneyExtension extends AbstractExtension
{
    private FormatterFactoryInterface $formatterFactory;

    public function __construct(FormatterFactoryInterface $formatterFactory)
    {
        $this->formatterFactory = $formatterFactory;
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

    public function formatMoney(Money $money, string $format = 'intl_money', ?string $locale = null, array $options = []): string
    {
        return $this->formatterFactory->createFormatter($format, $locale, $options)->format($money);
    }
}
