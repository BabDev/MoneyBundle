<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Twig;

use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Twig\MoneyExtension;
use Money\Money;
use Money\MoneyFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

        $this->extension = new MoneyExtension($this->formatterFactory);
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
