<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Twig;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Twig\MoneyExtension;
use Money\Money;
use Money\MoneyFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class MoneyExtensionTest extends TestCase
{
    /**
     * @var MockObject&FormatterFactoryInterface
     */
    private $formatterFactory;

    /**
     * @var MoneyExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->formatterFactory = $this->createMock(FormatterFactoryInterface::class);

        $this->extension = new MoneyExtension($this->formatterFactory, 'USD');
    }

    public function testExtensionRegistersFilters(): void
    {
        $this->assertContainsOnlyInstancesOf(
            TwigFilter::class,
            $this->extension->getFilters()
        );
    }

    public function testExtensionRegistersFunctions(): void
    {
        $this->assertContainsOnlyInstancesOf(
            TwigFunction::class,
            $this->extension->getFunctions()
        );
    }

    public function testMoneyIsCreatedWithDefaultCurrency(): void
    {
        $this->assertEquals(
            Money::USD(100),
            $this->extension->createMoney(100)
        );
    }

    public function testMoneyIsCreatedWithCustomCurrency(): void
    {
        $this->assertEquals(
            Money::EUR(100),
            $this->extension->createMoney('100', 'EUR')
        );
    }

    public function testMoneyIsFormatted(): void
    {
        $money = Money::USD(100);

        /** @var MockObject&MoneyFormatter $formatter */
        $formatter = $this->createMock(MoneyFormatter::class);
        $formatter->expects($this->once())
            ->method('format')
            ->with($money)
            ->willReturn('$1.00');

        $this->formatterFactory->expects($this->once())
            ->method('createFormatter')
            ->willReturn($formatter);

        $this->assertSame('$1.00', $this->extension->formatMoney($money));
    }
}
