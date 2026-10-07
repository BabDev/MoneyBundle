<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Serializer\Handler;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use Money\Currency;
use Money\Money;

final class MoneyHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
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

    /**
     * @throws InvalidArgumentException if a {@see Money} instance could not be created from the serialized data
     */
    public function deserializeMoneyFromJson(DeserializationVisitorInterface $visitor, mixed $moneyAsArray, array $type, DeserializationContext $context): Money
    {
        if (!\is_array($moneyAsArray)) {
            throw new InvalidArgumentException(\sprintf('Could not deserialize Money data, expected an array but got "%s".', get_debug_type($moneyAsArray)));
        }

        return $this->createMoney($moneyAsArray['amount'] ?? null, $moneyAsArray['currency'] ?? null);
    }

    /**
     * @throws InvalidArgumentException if a {@see Money} instance could not be created from the serialized data
     */
    public function deserializeMoneyFromXml(XmlDeserializationVisitor $visitor, \SimpleXMLElement $moneyAsXml, array $type, DeserializationContext $context): Money
    {
        return $this->createMoney(
            isset($moneyAsXml->amount) ? (string) $moneyAsXml->amount : null,
            isset($moneyAsXml->currency) ? (string) $moneyAsXml->currency : null,
        );
    }

    /**
     * @param array{name: string, params: array} $type
     *
     * @return array<string, string>|\ArrayObject<string, string>
     */
    public function serializeMoneyToJson(JsonSerializationVisitor $visitor, Money $money, array $type, SerializationContext $context)
    {
        // @phpstan-ignore-next-line return.type
        return $visitor->visitArray(
            [
                'amount' => $money->getAmount(),
                'currency' => $money->getCurrency()->getCode(),
            ],
            $type
        );
    }

    /**
     * Serializes a {@see Money} instance to a "money" root element, or to the current element when the instance is nested in another value.
     */
    public function serializeMoneyToXml(XmlSerializationVisitor $visitor, Money $money, array $type, SerializationContext $context): ?\DOMElement
    {
        $amountNode = $visitor->getDocument()->createElement('amount');
        $amountNode->nodeValue = $money->getAmount();

        $currencyNode = $visitor->getDocument()->createElement('currency');
        $currencyNode->nodeValue = $money->getCurrency()->getCode();

        // When nested, the visitor has already created the element for the property or array entry
        $currentNode = $visitor->getCurrentNode();

        if (null !== $currentNode) {
            $currentNode->appendChild($amountNode);
            $currentNode->appendChild($currencyNode);

            return null;
        }

        $moneyNode = $visitor->getDocument()->createElement('money');
        $moneyNode->appendChild($amountNode);
        $moneyNode->appendChild($currencyNode);

        return $moneyNode;
    }

    /**
     * @throws InvalidArgumentException if a {@see Money} instance could not be created from the serialized data
     */
    private function createMoney(mixed $amount, mixed $currency): Money
    {
        if (null === $amount || null === $currency) {
            throw new InvalidArgumentException('Could not deserialize Money data, the "amount" and "currency" values are required.');
        }

        if (!\is_int($amount) && !\is_string($amount)) {
            throw new InvalidArgumentException(\sprintf('Could not deserialize Money data, the amount must be an integer or a string but got "%s".', get_debug_type($amount)));
        }

        if (!\is_string($currency) || '' === $currency) {
            throw new InvalidArgumentException('Could not deserialize Money data, the currency must be a non-empty string.');
        }

        if (\is_string($amount) && !is_numeric($amount)) {
            throw new InvalidArgumentException('Could not deserialize Money data, the amount must be an integer amount in the currency\'s minor unit.');
        }

        try {
            return new Money($amount, new Currency($currency));
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidArgumentException('Could not deserialize Money data, the amount must be an integer amount in the currency\'s minor unit.', $exception->getCode(), $exception);
        }
    }
}
