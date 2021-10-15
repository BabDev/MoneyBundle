<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Form\DataTransformer;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Form\DataTransformer\MoneyToLocalizedStringTransformer;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Intl\Util\IntlTestHelper;

final class MoneyToLocalizedStringTransformerTest extends TestCase
{
    /**
     * @var false|string
     */
    private $previousLocale;

    protected function setUp(): void
    {
        $this->previousLocale = setlocale(\LC_ALL, '0');
    }

    protected function tearDown(): void
    {
        setlocale(\LC_ALL, $this->previousLocale);
    }

    public function testConstructorRejectsInvalidParams(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MoneyToLocalizedStringTransformer(new FormatterFactory('de_AT'), new ParserFactory('de_AT'), new Currency('EUR'), 'test');
    }

    public function testTransform(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('de_AT'), new ParserFactory('de_AT'), new Currency('EUR'), new NumberToLocalizedStringTransformer());

        self::assertEquals('1,23', $transformer->transform(Money::EUR(123)));
    }

    /**
     * @group legacy
     */
    public function testTransformLegacyConstructor(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('de_AT'), new ParserFactory('de_AT'), new Currency('EUR'), null);

        self::assertEquals('1,23', $transformer->transform(Money::EUR(123)));
    }

    public function testTransformExpectsMoney(): void
    {
        $this->expectException(TransformationFailedException::class);

        (new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer()))
            ->transform('abcd');
    }

    public function testTransformEmpty(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer());

        self::assertSame('', $transformer->transform(null));
    }

    public function testReverseTransform(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('de_AT'), new ParserFactory('de_AT'), new Currency('EUR'), new NumberToLocalizedStringTransformer());

        self::assertEquals(Money::EUR(123), $transformer->reverseTransform('1,23'));
    }

    /**
     * @group legacy
     */
    public function testReverseTransformLegacyConstructor(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('de_AT'), new ParserFactory('de_AT'), new Currency('EUR'), null);

        self::assertEquals(Money::EUR(123), $transformer->reverseTransform('1,23'));
    }

    public function testReverseTransformExpectsString(): void
    {
        $this->expectException(TransformationFailedException::class);

        (new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer()))
            ->reverseTransform(12345);
    }

    public function testReverseTransformEmpty(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer());

        self::assertNull($transformer->reverseTransform(''));
    }
}
