<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Serializer\Handler;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
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
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => Money::class,
                'method' => 'serializeMoneyToXml',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => Money::class,
                'method' => 'deserializeMoneyFromXml',
            ],
        ];
    }

    public function deserializeMoneyFromJson(DeserializationVisitorInterface $visitor, array $moneyAsArray, array $type, DeserializationContext $context): Money
    {
        return new Money($moneyAsArray['amount'], new Currency($moneyAsArray['currency']));
    }

    public function deserializeMoneyFromXml(XmlDeserializationVisitor $visitor, \SimpleXMLElement $moneyAsXml, array $type, DeserializationContext $context): Money
    {
        /** @phpstan-var numeric-string $amount */
        $amount = (string) $moneyAsXml->amount;

        return new Money($amount, new Currency((string) $moneyAsXml->currency));
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

    public function serializeMoneyToXml(XmlSerializationVisitor $visitor, Money $money, array $type, SerializationContext $context): \DOMElement
    {
        $amountNode = $visitor->getDocument()->createElement('amount');
        $amountNode->nodeValue = $money->getAmount();

        $currencyNode = $visitor->getDocument()->createElement('currency');
        $currencyNode->nodeValue = $money->getCurrency()->getCode();

        $moneyNode = $visitor->getDocument()->createElement('money');
        $moneyNode->appendChild($amountNode);
        $moneyNode->appendChild($currencyNode);

        return $moneyNode;
    }
}
