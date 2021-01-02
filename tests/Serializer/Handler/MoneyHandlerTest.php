<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Serializer\Handler;

use BabDev\MoneyBundle\Serializer\Handler\MoneyHandler;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MoneyHandlerTest extends TestCase
{
    public function testSerializeMoneyToJson(): void
    {
        $expectedResultArray = [
            'amount' => '100',
            'currency' => 'USD',
        ];

        /** @var MockObject&SerializationContext $context */
        $context = $this->createMock(SerializationContext::class);

        /** @var MockObject&SerializationVisitorInterface $visitor */
        $visitor = $this->createMock(SerializationVisitorInterface::class);
        $visitor->expects($this->once())
            ->method('visitArray')
            ->with($this->isType('array'), [])
            ->willReturn($expectedResultArray);

        $this->assertEquals(
            $expectedResultArray,
            (new MoneyHandler())->serializeMoneyToJson($visitor, Money::USD(1000), [], $context)
        );
    }

    public function testDeserializeMoneyFromJson(): void
    {
        /** @var MockObject&DeserializationContext $context */
        $context = $this->createMock(DeserializationContext::class);

        /** @var MockObject&DeserializationVisitorInterface $visitor */
        $visitor = $this->createMock(DeserializationVisitorInterface::class);

        $this->assertEquals(
            Money::USD(1000),
            (new MoneyHandler())->deserializeMoneyFromJson($visitor, ['amount' => '1000', 'currency' => 'USD'], [], $context)
        );
    }
}
