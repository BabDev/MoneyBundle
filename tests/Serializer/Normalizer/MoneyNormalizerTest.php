<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Serializer\Normalizer;

use BabDev\MoneyBundle\Serializer\Normalizer\MoneyNormalizer;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

final class MoneyNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $this->assertEquals(
            ['amount' => '100', 'currency' => 'USD'],
            (new MoneyNormalizer())->normalize(new Money(100, new Currency('USD')))
        );
    }

    public function testNormalizeOnlyAcceptsMoneyInstances(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The object must be an instance of "%s".', Money::class));

        (new MoneyNormalizer())->normalize(new \stdClass());
    }

    public function dataSupportsNormalization(): \Generator
    {
        yield 'Supported' => [new Money(100, new Currency('USD')), true];
        yield 'Not Supported' => [new \stdClass(), false];
    }

    /**
     * @dataProvider dataSupportsNormalization
     */
    public function testSupportsNormalization($data, bool $supported): void
    {
        $this->assertSame($supported, (new MoneyNormalizer())->supportsNormalization($data));
    }

    public function testDenormalize(): void
    {
        $this->assertEquals(
            new Money(100, new Currency('USD')),
            (new MoneyNormalizer())->denormalize(['amount' => '100', 'currency' => 'USD'], Money::class)
        );
    }

    public function testDenormalizeOnlyAcceptsArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Data expected to be an array, "%s" given.', \stdClass::class));

        (new MoneyNormalizer())->denormalize(new \stdClass(), Money::class);
    }

    public function testDenormalizeValidatesArrayKeys(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing required keys from data array, must provide "amount" and "currency".');

        (new MoneyNormalizer())->denormalize([], Money::class);
    }

    public function testDenormalizeConvertsExceptionsCreatingMoneyInstances(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        (new MoneyNormalizer())->denormalize(['amount' => '9.99', 'currency' => 'USD'], Money::class);
    }

    public function dataSupportsDenormalization(): \Generator
    {
        yield 'Supported' => [new \stdClass(), Money::class, true];
        yield 'Not Supported' => [new \stdClass(), \stdClass::class, false];
    }

    /**
     * @dataProvider dataSupportsDenormalization
     */
    public function testSupportsDenormalization($data, string $type, bool $supported): void
    {
        $this->assertSame($supported, (new MoneyNormalizer())->supportsDenormalization($data, $type));
    }

    public function testHasCacheableSupportsMethod(): void
    {
        $this->assertTrue((new MoneyNormalizer())->hasCacheableSupportsMethod());
    }
}
