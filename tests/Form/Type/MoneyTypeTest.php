<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Form\Type;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Form\Type\MoneyType;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * Test class for the MoneyType form.
 *
 * Class is based on \Symfony\Component\Form\Tests\Extension\Core\Type\MoneyTypeTest
 */
final class MoneyTypeTest extends TypeTestCase
{
    /**
     * @var string
     */
    private $defaultLocale;

    protected function setUp(): void
    {
        // we test against different locales, so we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        parent::setUp();

        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        \Locale::setDefault($this->defaultLocale);
    }

    public function dataPassMoneyPatternToView(): \Generator
    {
        yield 'USD with en_US locale' => ['$ {{ widget }}', 'en_US', new Currency('USD')];
        yield 'EUR with en_US locale' => ['€ {{ widget }}', 'en_US', new Currency('EUR')];
        yield 'USD with de_DE locale' => ['{{ widget }} $', 'de_DE', new Currency('USD')];
        yield 'EUR with de_DE locale' => ['{{ widget }} €', 'de_DE', new Currency('EUR')];
    }

    /**
     * @dataProvider dataPassMoneyPatternToView
     */
    public function testPassMoneyPatternToView(string $expected, string $locale, Currency $currency): void
    {
        \Locale::setDefault($locale);

        $view = $this->factory->create(MoneyType::class, null, ['currency' => $currency])
            ->createView();

        $this->assertSame($expected, $view->vars['money_pattern']);
    }

    public function testSubmitNull(): void
    {
        $form = $this->factory->create(MoneyType::class);
        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertNull($form->getNormData());
        $this->assertSame('', $form->getViewData());
    }

    public function testSubmitNullUsesDefaultEmptyData(): void
    {
        $expected = Money::USD(1000);

        $form = $this->factory->create(MoneyType::class, null, ['empty_data' => '10.00']);
        $form->submit(null);

        $this->assertSame('10.00', $form->getViewData());
        $this->assertEquals($expected, $form->getNormData());
        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitValue(): void
    {
        $form = $this->factory->create(MoneyType::class);
        $form->submit('12345.67');

        $this->assertEquals(Money::USD(1234567), $form->getData());
        $this->assertEquals(Money::USD(1234567), $form->getNormData());
        $this->assertSame('12345.67', $form->getViewData());
    }

    public function testDefaultFormattingWithDefaultRounding(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['scale' => 0]);
        $form->setData(Money::USD(1234554));

        $this->assertSame('12346', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithSpecifiedRounding(): void
    {
        $form = $this->factory->create(MoneyType::class, null, ['scale' => 0, 'rounding_mode' => \NumberFormatter::ROUND_DOWN]);
        $form->setData(Money::USD(1234554));

        $this->assertSame('12345', $form->createView()->vars['value']);
    }

    public function testHtml5EnablesSpecificFormatting(): void
    {
        // Since we test against "de_CH", we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('de_CH');

        $form = $this->factory->create(MoneyType::class, null, ['currency' => new Currency('EUR'), 'html5' => true, 'scale' => 2]);
        $form->setData(Money::EUR(1234560));

        $this->assertSame('12345.60', $form->createView()->vars['value']);
        $this->assertSame('number', $form->createView()->vars['type']);
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
