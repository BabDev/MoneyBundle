<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Form\Type;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Form\Type\MoneyType;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * Test class for the MoneyType form.
 *
 * Class is based on \Symfony\Component\Form\Tests\Extension\Core\Type\MoneyTypeTest
 */
#[AllowMockObjectsWithoutExpectations]
final class MoneyTypeTest extends TypeTestCase
{
    private ?string $defaultLocale = null;

    protected function setUp(): void
    {
        // we test against different locales, so we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        parent::setUp();

        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (null !== $this->defaultLocale) {
            \Locale::setDefault($this->defaultLocale);
        }
    }

    public static function dataPassMoneyPatternToView(): \Generator
    {
        yield 'USD with en_US locale' => ['$ {{ widget }}', 'en_US', new Currency('USD')];
        yield 'EUR with en_US locale' => ['€ {{ widget }}', 'en_US', new Currency('EUR')];
        yield 'USD with de_DE locale' => ['{{ widget }} $', 'de_DE', new Currency('USD')];
        yield 'EUR with de_DE locale' => ['{{ widget }} €', 'de_DE', new Currency('EUR')];
    }

    #[DataProvider('dataPassMoneyPatternToView')]
    public function testPassMoneyPatternToView(string $expected, string $locale, Currency $currency): void
    {
        \Locale::setDefault($locale);

        $view = $this->factory->create(MoneyType::class, null, ['currency' => $currency])
            ->createView();

        self::assertSame($expected, $view->vars['money_pattern']);
    }

    public function testSubmitNull(): void
    {
        $form = $this->factory->create(MoneyType::class);
        $form->submit(null);

        self::assertNull($form->getData());
        self::assertNull($form->getNormData());
        self::assertSame('', $form->getViewData());
    }

    public function testSubmitNullUsesDefaultEmptyData(): void
    {
        $expected = Money::USD(1000);

        $form = $this->factory->create(MoneyType::class, null, ['empty_data' => '10.00']);
        $form->submit(null);

        self::assertSame('10.00', $form->getViewData());
        self::assertEquals($expected, $form->getNormData());
        self::assertEquals($expected, $form->getData());
    }

    public function testSubmitValue(): void
    {
        $form = $this->factory->create(MoneyType::class);
        $form->submit('12345.67');

        self::assertEquals(Money::USD(1234567), $form->getData());
        self::assertEquals(Money::USD(1234567), $form->getNormData());
        self::assertSame('12345.67', $form->getViewData());
    }

    public function testDefaultFormattingWithDefaultRounding(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['scale' => 0]);
        $form->setData(Money::USD(1234554));

        self::assertSame('12346', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithSpecifiedRounding(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['scale' => 0, 'rounding_mode' => \NumberFormatter::ROUND_DOWN]);
        $form->setData(Money::USD(1234554));

        self::assertSame('12345', $form->createView()->vars['value']);
    }

    public function testHtml5EnablesSpecificFormatting(): void
    {
        // Since we test against "de_CH", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_CH');

        $form = $this->factory->create(MoneyType::class, null, ['currency' => new Currency('EUR'), 'html5' => true, 'scale' => 2]);
        $form->setData(Money::EUR(1234560));

        $view = $form->createView();

        self::assertSame('12345.60', $view->vars['value']);
        self::assertSame('number', $view->vars['type']);
    }

    public function testHtml5AddsStepAttributeIfNotSet(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['html5' => true]);

        self::assertSame('any', $form->createView()->vars['attr']['step']); // @phpstan-ignore-line offsetAccess.nonOffsetAccessible

        $form = $this->factory->create(MoneyType::class, null, ['html5' => false, 'scale' => 2]);

        self::assertSame('decimal', $form->createView()->vars['attr']['inputmode']); // @phpstan-ignore-line offsetAccess.nonOffsetAccessible

        $form = $this->factory->create(MoneyType::class, null, ['html5' => false, 'scale' => 0]);

        self::assertSame('numeric', $form->createView()->vars['attr']['inputmode']); // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
    }

    public function testHtml5DoesNotOverrideUserProvidedStep(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['html5' => true, 'attr' => ['step' => '0.01']]);

        self::assertSame('0.01', $form->createView()->vars['attr']['step']); // @phpstan-ignore-line offsetAccess.nonOffsetAccessible

        $form = $this->factory->create(MoneyType::class, null, ['html5' => false, 'scale' => 2]);

        self::assertSame('decimal', $form->createView()->vars['attr']['inputmode']); // @phpstan-ignore-line offsetAccess.nonOffsetAccessible
    }

    public function testDefaultInput(): void
    {
        $form = $this->factory->create(MoneyType::class);
        $form->submit('12345.67');

        self::assertEquals(Money::USD(1234567), $form->getData());
    }

    public function testIntegerInput(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['input' => 'integer']);
        $form->submit('12345.67');

        self::assertEquals(Money::USD(1234567), $form->getData());
    }

    public function testSubmitStringInputWithDefaultScale(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['input' => 'string']);
        $form->submit('1.234');

        self::assertEquals(Money::USD(123), $form->getData());
        self::assertEquals(Money::USD(123), $form->getNormData());
        self::assertSame('1.23', $form->getViewData());
    }

    public function testSubmitStringInputWithScale(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['input' => 'string', 'scale' => 3]);
        $form->submit('1.234');

        self::assertEquals(Money::USD(123), $form->getData());
        self::assertEquals(Money::USD(123), $form->getNormData());
        self::assertSame('1.230', $form->getViewData());
    }

    protected function getExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        new MoneyType(new FormatterFactory('en_US'), new ParserFactory('en_US'), 'USD'),
                    ],
                    []
                ),
            ]
        );
    }
}
