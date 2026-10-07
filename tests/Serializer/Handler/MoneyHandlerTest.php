<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Serializer\Handler;

use BabDev\MoneyBundle\Serializer\Handler\MoneyHandler;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyHandlerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(SerializerBuilder::class)) {
            self::markTestSkipped('Test requires JMS Serializer');
        }
    }

    public function testSerializeMoneyToJson(): void
    {
        self::assertJsonStringEqualsJsonString(
            '{"amount":"1000","currency":"USD"}',
            $this->createSerializer()->serialize(Money::USD(1000), 'json'),
        );
    }

    public function testSerializeMoneyToXml(): void
    {
        $expectedXml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <money>
              <amount>1000</amount>
              <currency>USD</currency>
            </money>

            XML;

        self::assertXmlStringEqualsXmlString(
            $expectedXml,
            $this->createSerializer()->serialize(Money::USD(1000), 'xml'),
        );
    }

    public function testDeserializeMoneyFromJson(): void
    {
        self::assertEquals(
            Money::USD(1000),
            $this->createSerializer()->deserialize('{"amount":"1000","currency":"USD"}', Money::class, 'json'),
        );
    }

    public function testDeserializeMoneyFromXml(): void
    {
        $generatedXml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <money>
              <amount>1000</amount>
              <currency>USD</currency>
            </money>

            XML;

        self::assertEquals(
            Money::USD(1000),
            $this->createSerializer()->deserialize($generatedXml, Money::class, 'xml'),
        );
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function dataInvalidJson(): \Generator
    {
        yield 'Not an object' => ['"1000 USD"', 'Could not deserialize Money data, expected an array but got "string".'];
        yield 'Missing currency' => ['{"amount":"1000"}', 'Could not deserialize Money data, the "amount" and "currency" values are required.'];
        yield 'Float amount' => ['{"amount":9.99,"currency":"USD"}', 'Could not deserialize Money data, the amount must be an integer or a string but got "float".'];
        yield 'Decimal amount' => ['{"amount":"9.99","currency":"USD"}', 'Could not deserialize Money data, the amount must be an integer amount in the currency\'s minor unit.'];
        yield 'Non-numeric amount' => ['{"amount":"$1000","currency":"USD"}', 'Could not deserialize Money data, the amount must be an integer amount in the currency\'s minor unit.'];
        yield 'Empty currency' => ['{"amount":"1000","currency":""}', 'Could not deserialize Money data, the currency must be a non-empty string.'];
        yield 'Integer currency' => ['{"amount":"1000","currency":840}', 'Could not deserialize Money data, the currency must be a non-empty string.'];
    }

    #[DataProvider('dataInvalidJson')]
    public function testDeserializeMoneyFromJsonRejectsInvalidData(string $json, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->createSerializer()->deserialize($json, Money::class, 'json');
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function dataInvalidXml(): \Generator
    {
        yield 'Missing currency' => ['<money><amount>1000</amount></money>', 'Could not deserialize Money data, the "amount" and "currency" values are required.'];
        yield 'Decimal amount' => ['<money><amount>9.99</amount><currency>USD</currency></money>', 'Could not deserialize Money data, the amount must be an integer amount in the currency\'s minor unit.'];
        yield 'Empty currency' => ['<money><amount>1000</amount><currency></currency></money>', 'Could not deserialize Money data, the currency must be a non-empty string.'];
    }

    #[DataProvider('dataInvalidXml')]
    public function testDeserializeMoneyFromXmlRejectsInvalidData(string $xml, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->createSerializer()->deserialize('<?xml version="1.0" encoding="UTF-8"?>'.$xml, Money::class, 'xml');
    }

    private function createSerializer(): SerializerInterface
    {
        $registry = new HandlerRegistry();
        $registry->registerSubscribingHandler(new MoneyHandler());

        return SerializerBuilder::create($registry, new EventDispatcher())->build();
    }
}
