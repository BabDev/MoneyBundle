<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Form\DataTransformer;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Form\DataTransformer\MoneyToLocalizedStringTransformer;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Intl\Util\IntlTestHelper;

final class MoneyToLocalizedStringTransformerTest extends TestCase
{
    private string|bool $previousLocale;

    private string $previousPrecision;

    protected function setUp(): void
    {
        $this->previousLocale = setlocale(\LC_ALL, '0');
        $this->previousPrecision = \ini_get('precision');
    }

    protected function tearDown(): void
    {
        setlocale(\LC_ALL, $this->previousLocale);
        ini_set('precision', $this->previousPrecision);
    }

    public function testTransform(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('de_AT'), new ParserFactory('de_AT'), new Currency('EUR'), new NumberToLocalizedStringTransformer());

        self::assertEquals('1,23', $transformer->transform(Money::EUR(123)));
    }

    public function testTransformExpectsMoney(): void
    {
        $this->expectException(TransformationFailedException::class);

        new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer())
            ->transform('abcd'); // @phpstan-ignore-line argument.type
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

    public function testReverseTransformExpectsString(): void
    {
        $this->expectException(TransformationFailedException::class);

        new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer())
            ->reverseTransform(12345); // @phpstan-ignore-line argument.type
    }

    public function testReverseTransformEmpty(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer());

        self::assertNull($transformer->reverseTransform(''));
    }

    public function testTransformUsesTheCurrencySubunitAsTheDefaultScale(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('JPY'), new NumberToLocalizedStringTransformer(0), 'en');

        self::assertSame('1234', $transformer->transform(Money::JPY(1234)));
        self::assertEquals(Money::JPY(1234), $transformer->reverseTransform('1234'));
    }

    public function testTransformAmountTooLargeToConvertPrecisely(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('de_AT'), new ParserFactory('de_AT'), new Currency('EUR'), new NumberToLocalizedStringTransformer(2));

        self::assertSame('1234567890123456,78', $transformer->transform(Money::EUR('123456789012345678')));
        self::assertSame('-1234567890123456,78', $transformer->transform(Money::EUR('-123456789012345678')));
    }

    /**
     * @return \Generator<string, array{int, string, string}>
     */
    public static function provideRoundingModes(): \Generator
    {
        yield 'ceiling' => [\NumberFormatter::ROUND_CEILING, '12345678901234568', '-12345678901234567'];
        yield 'floor' => [\NumberFormatter::ROUND_FLOOR, '12345678901234567', '-12345678901234568'];
        yield 'up' => [\NumberFormatter::ROUND_UP, '12345678901234568', '-12345678901234568'];
        yield 'down' => [\NumberFormatter::ROUND_DOWN, '12345678901234567', '-12345678901234567'];
        yield 'half up' => [\NumberFormatter::ROUND_HALFUP, '12345678901234568', '-12345678901234568'];
        yield 'half down' => [\NumberFormatter::ROUND_HALFDOWN, '12345678901234567', '-12345678901234567'];
        yield 'half even' => [\NumberFormatter::ROUND_HALFEVEN, '12345678901234568', '-12345678901234568'];
    }

    #[DataProvider('provideRoundingModes')]
    public function testTransformAmountTooLargeToConvertPreciselyRoundsToTheScale(int $roundingMode, string $expectedPositive, string $expectedNegative): void
    {
        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer(0, false, $roundingMode), 'en', 0, $roundingMode);

        self::assertSame($expectedPositive, $transformer->transform(Money::USD('1234567890123456750')));
        self::assertSame($expectedNegative, $transformer->transform(Money::USD('-1234567890123456750')));
    }

    public function testReverseTransformRejectsAmountsTooLargeToConvertPrecisely(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The amount has more than 14 significant digits and cannot be converted precisely.');

        new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer(2), 'en')
            ->reverseTransform('1000000000000.00');
    }

    /**
     * @return \Generator<string, array{string, int, string, numeric-string, string}>
     */
    public static function providePrecisionSettings(): \Generator
    {
        yield 'lower precision' => ['10', 10, '99999999.99', '9999999999', '100000000.00'];
        yield 'no precision' => ['0', 1, '0.09', '9', '0.10'];
        yield 'precision beyond a float' => ['17', 15, '9999999999999.99', '999999999999999', '10000000000000.00'];
        yield 'shortest exact representation' => ['-1', 15, '9999999999999.99', '999999999999999', '10000000000000.00'];
    }

    /**
     * @param numeric-string $largestPreciseAmount
     */
    #[DataProvider('providePrecisionSettings')]
    public function testPreciseDigitsFollowThePrecisionSetting(string $precision, int $maxPreciseDigits, string $largestPrecise, string $largestPreciseAmount, string $smallestImprecise): void
    {
        ini_set('precision', $precision);

        $transformer = new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer(2), 'en');

        self::assertEquals(Money::USD($largestPreciseAmount), $transformer->reverseTransform($largestPrecise));

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(\sprintf('The amount has more than %d significant digits and cannot be converted precisely.', $maxPreciseDigits));

        $transformer->reverseTransform($smallestImprecise);
    }

    public function testAmountsChangedByALowerPrecisionSettingAreRejected(): void
    {
        ini_set('precision', '10');

        $this->expectException(TransformationFailedException::class);

        new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer(2), 'en')
            ->reverseTransform('123456789012.34');
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function provideInvalidScales(): \Generator
    {
        yield 'greater than the subunit' => [3];
        yield 'negative' => [-1];
    }

    #[DataProvider('provideInvalidScales')]
    public function testScaleMustBeWithinTheCurrencySubunit(int $scale): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The scale must be between 0 and the number of decimal places used by the "USD" currency (2), %d given.', $scale));

        new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('USD'), new NumberToLocalizedStringTransformer($scale), null, $scale);
    }

    public function testCurrencyMustBeSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "XYZ" currency is not supported.');

        new MoneyToLocalizedStringTransformer(new FormatterFactory('en_US'), new ParserFactory('en_US'), new Currency('XYZ'), new NumberToLocalizedStringTransformer());
    }
}
