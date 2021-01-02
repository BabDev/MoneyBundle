<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Serializer\Handler;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Money\Currency;
use Money\Money;

final class MoneyHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Money::class,
                'method' => 'serializeMoneyToJson',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => Money::class,
                'method' => 'deserializeMoneyFromJson',
            ],
        ];
    }

    public function deserializeMoneyFromJson(DeserializationVisitorInterface $visitor, array $moneyAsArray, array $type, DeserializationContext $context): Money
    {
        return new Money($moneyAsArray['amount'], new Currency($moneyAsArray['currency']));
    }

    /**
     * @return array|\ArrayObject
     */
    public function serializeMoneyToJson(SerializationVisitorInterface $visitor, Money $money, array $type, SerializationContext $context)
    {
        return $visitor->visitArray(
            [
                'amount' => $money->getAmount(),
                'currency' => $money->getCurrency()->getCode(),
            ],
            $type
        );
    }
}
