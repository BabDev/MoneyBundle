<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Serializer\Handler;

use BabDev\MoneyBundle\Serializer\Handler\MoneyHandler;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Money\Money;
use PHPUnit\Framework\TestCase;

final class MoneyHandlerTest extends TestCase
{
    public function testSerializeMoneyToJson(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"amount":"1000","currency":"USD"}',
            $this->createSerializer()->serialize(Money::USD(1000), 'json')
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

        $this->assertXmlStringEqualsXmlString(
            $expectedXml,
            $this->createSerializer()->serialize(Money::USD(1000), 'xml')
        );
    }

    public function testDeserializeMoneyFromJson(): void
    {
        $this->assertEquals(
            Money::USD(1000),
            $this->createSerializer()->deserialize('{"amount":"1000","currency":"USD"}', Money::class, 'json')
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
        $this->assertEquals(
            Money::USD(1000),
            $this->createSerializer()->deserialize($generatedXml, Money::class, 'xml')
        );
    }

    private function createSerializer(): SerializerInterface
    {
        $registry = new HandlerRegistry();
        $registry->registerSubscribingHandler(new MoneyHandler());

        return SerializerBuilder::create($registry, new EventDispatcher())->build();
    }
}
