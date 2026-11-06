<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Form\Extension;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Form\Extension\MoneyBoundsTypeExtension;
use BabDev\MoneyBundle\Form\Type\MoneyType;
use Money\Currency;
use Money\Exception\CurrencyMismatchException;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\BoundsType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

final class MoneyBoundsTypeExtensionTest extends TestCase
{
    private FormFactoryInterface $factory;

    protected function setUp(): void
    {
        if (!class_exists(BoundsType::class)) {
            self::markTestSkipped('The BoundsType form type requires Symfony 8.2 or later.');
        }

        $this->factory = Forms::createFormFactoryBuilder()
            ->addType(new MoneyType(new FormatterFactory('en_US'), new ParserFactory('en_US'), 'USD'))
            ->addTypeExtension(new MoneyBoundsTypeExtension())
            ->getFormFactory();
    }

    public static function dataComparedBounds(): \Generator
    {
        yield 'lower bound less than the upper bound' => ['1.00', '5.50', true];
        yield 'equal bounds' => ['5.50', '5.50', true];
        yield 'lower bound greater than the upper bound' => ['5.50', '1.00', false];
        yield 'lower bound greater than the upper bound by one minor unit' => ['1.01', '1.00', false];
    }

    #[DataProvider('dataComparedBounds')]
    public function testComparesMoneyBounds(string $from, string $to, bool $valid): void
    {
        $form = $this->factory->create(BoundsType::class, null, ['type' => MoneyType::class, 'compare' => true]);
        $form->submit(['from' => $from, 'to' => $to]);

        self::assertTrue($form->isSynchronized());
        self::assertSame($valid, 0 === $form->get('from')->getErrors()->count());
    }

    public function testKeepsTheBoundsData(): void
    {
        $form = $this->factory->create(BoundsType::class, null, ['type' => MoneyType::class, 'compare' => true]);
        $form->submit(['from' => '1.00', 'to' => '5.50']);

        self::assertEquals(['from' => Money::USD(100), 'to' => Money::USD(550)], $form->getData());
    }

    public function testDoesNotCompareHalfOpenRanges(): void
    {
        $form = $this->factory->create(BoundsType::class, null, ['type' => MoneyType::class, 'compare' => true]);
        $form->submit(['from' => '5.50', 'to' => '']);

        self::assertCount(0, $form->get('from')->getErrors());
        self::assertEquals(['from' => Money::USD(550), 'to' => null], $form->getData());
    }

    public function testDoesNotCompareWhenTheComparisonIsDisabled(): void
    {
        $form = $this->factory->create(BoundsType::class, null, ['type' => MoneyType::class]);
        $form->submit(['from' => '5.50', 'to' => '1.00']);

        self::assertCount(0, $form->get('from')->getErrors());
    }

    public function testKeepsACallableComparison(): void
    {
        // A comparison treating every pair of bounds as out of order
        $form = $this->factory->create(BoundsType::class, null, ['type' => MoneyType::class, 'compare' => static fn (Money $from, Money $to): int => 1]);
        $form->submit(['from' => '1.00', 'to' => '5.50']);

        self::assertCount(1, $form->get('from')->getErrors());
    }

    public function testDoesNotChangeTheComparisonOfOtherTypes(): void
    {
        $form = $this->factory->create(BoundsType::class, null, ['type' => IntegerType::class, 'compare' => true]);
        $form->submit(['from' => '5', 'to' => '1']);

        self::assertCount(1, $form->get('from')->getErrors());
    }

    public function testBoundsInDifferentCurrenciesCannotBeCompared(): void
    {
        $form = $this->factory->create(BoundsType::class, null, [
            'type' => MoneyType::class,
            'compare' => true,
            'to_options' => ['currency' => new Currency('EUR')],
        ]);

        $this->expectException(CurrencyMismatchException::class);

        $form->submit(['from' => '1.00', 'to' => '5.50']);
    }
}
