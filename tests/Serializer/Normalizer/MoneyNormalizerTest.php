<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Serializer\Normalizer;

use BabDev\MoneyBundle\Serializer\Normalizer\MoneyNormalizer;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MoneyNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        self::assertEquals(
            ['amount' => '100', 'currency' => 'USD'],
            new MoneyNormalizer()->normalize(new Money(100, new Currency('USD'))),
        );
    }

    public function testNormalizeOnlyAcceptsMoneyInstances(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The object must be an instance of "%s".', Money::class));

        new MoneyNormalizer()->normalize(new \stdClass());
    }

    public static function dataSupportsNormalization(): \Generator
    {
        yield 'Supported' => [new Money(100, new Currency('USD')), true];
        yield 'Not Supported' => [new \stdClass(), false];
    }

    #[DataProvider('dataSupportsNormalization')]
    public function testSupportsNormalization(mixed $data, bool $supported): void
    {
        self::assertSame($supported, new MoneyNormalizer()->supportsNormalization($data));
    }

    public function testDenormalize(): void
    {
        self::assertEquals(
            new Money(100, new Currency('USD')),
            new MoneyNormalizer()->denormalize(['amount' => '100', 'currency' => 'USD'], Money::class),
        );
    }

    public function testDenormalizeOnlyAcceptsArrays(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage(\sprintf('Data expected to be an array, "%s" given.', \stdClass::class));

        new MoneyNormalizer()->denormalize(new \stdClass(), Money::class);
    }

    public function testDenormalizeValidatesArrayKeys(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Missing required keys from data array, must provide "amount" and "currency".');

        new MoneyNormalizer()->denormalize([], Money::class);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, string, string}>
     */
    public static function dataInvalidMoneyData(): \Generator
    {
        yield 'Float amount' => [['amount' => 9.99, 'currency' => 'USD'], 'amount', 'The amount must be an integer or a string, "float" given.'];
        yield 'Boolean amount' => [['amount' => true, 'currency' => 'USD'], 'amount', 'The amount must be an integer or a string, "bool" given.'];
        yield 'Array amount' => [['amount' => [100], 'currency' => 'USD'], 'amount', 'The amount must be an integer or a string, "array" given.'];
        yield 'Decimal amount' => [['amount' => '9.99', 'currency' => 'USD'], 'amount', 'The amount must be an integer amount in the currency\'s minor unit.'];
        yield 'Non-numeric amount' => [['amount' => '$100', 'currency' => 'USD'], 'amount', 'The amount must be an integer amount in the currency\'s minor unit.'];
        yield 'Empty currency' => [['amount' => '100', 'currency' => ''], 'currency', 'The currency must be a non-empty string.'];
        yield 'Integer currency' => [['amount' => '100', 'currency' => 840], 'currency', 'The currency must be a non-empty string.'];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('dataInvalidMoneyData')]
    public function testDenormalizeRejectsInvalidData(array $data, string $key, string $message): void
    {
        try {
            new MoneyNormalizer()->denormalize($data, Money::class, null, ['deserialization_path' => 'price']);

            self::fail(\sprintf('A %s should have been thrown.', NotNormalizableValueException::class));
        } catch (NotNormalizableValueException $exception) {
            self::assertSame($message, $exception->getMessage());
            self::assertSame('price.'.$key, $exception->getPath());
            self::assertTrue($exception->canUseMessageForUser());
        }
    }

    public function testDenormalizationErrorsAreCollected(): void
    {
        $serializer = new Serializer([new MoneyNormalizer(), new ObjectNormalizer()], [new JsonEncoder()]);

        try {
            $serializer->deserialize(
                '{"total":{"amount":9.99,"currency":"USD"},"tax":{"amount":"100","currency":""}}',
                MoneyNormalizerTestInvoice::class,
                'json',
                [DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true],
            );

            self::fail(\sprintf('A %s should have been thrown.', PartialDenormalizationException::class));
        } catch (PartialDenormalizationException $exception) {
            $errors = [];

            foreach ($exception->getErrors() as $error) {
                self::assertInstanceOf(NotNormalizableValueException::class, $error);

                $errors[$error->getPath() ?? ''] = $error->getMessage();
            }

            self::assertSame('The amount must be an integer or a string, "float" given.', $errors['total.amount'] ?? null);
            self::assertSame('The currency must be a non-empty string.', $errors['tax.currency'] ?? null);
        }
    }

    public static function dataSupportsDenormalization(): \Generator
    {
        yield 'Supported' => [new \stdClass(), Money::class, true];
        yield 'Not Supported' => [new \stdClass(), \stdClass::class, false];
    }

    #[DataProvider('dataSupportsDenormalization')]
    public function testSupportsDenormalization(mixed $data, string $type, bool $supported): void
    {
        self::assertSame($supported, new MoneyNormalizer()->supportsDenormalization($data, $type));
    }
}

final readonly class MoneyNormalizerTestInvoice
{
    public function __construct(
        public Money $total,
        public Money $tax,
    ) {}
}
