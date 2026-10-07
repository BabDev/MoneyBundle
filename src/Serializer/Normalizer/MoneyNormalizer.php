<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Serializer\Normalizer;

use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class MoneyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @throws InvalidArgumentException when the object given is not a supported type for the normalizer
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (!$data instanceof Money) {
            throw new InvalidArgumentException(\sprintf('The object must be an instance of "%s".', Money::class));
        }

        return [
            'amount' => $data->getAmount(),
            'currency' => $data->getCurrency()->getCode(),
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Money;
    }

    /**
     * @throws NotNormalizableValueException if a {@see Money} instance cannot be created from the given data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Money
    {
        /** @var string|null $path */
        $path = $context['deserialization_path'] ?? null;

        if (!\is_array($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Data expected to be an array, "%s" given.', get_debug_type($data)), $data, ['array'], $path, true);
        }

        if (!isset($data['amount']) || !isset($data['currency'])) {
            throw new NotNormalizableValueException('Missing required keys from data array, must provide "amount" and "currency".', currentType: 'array', expectedTypes: ['array'], path: $path, useMessageForUser: true);
        }

        $amount = $data['amount'];
        $currency = $data['currency'];

        if (!\is_int($amount) && !\is_string($amount)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The amount must be an integer or a string, "%s" given.', get_debug_type($amount)), $amount, ['int', 'string'], $this->appendPath($path, 'amount'), true);
        }

        if (!\is_string($currency) || '' === $currency) {
            throw NotNormalizableValueException::createForUnexpectedDataType('The currency must be a non-empty string.', $currency, ['string'], $this->appendPath($path, 'currency'), true);
        }

        if (\is_string($amount) && !is_numeric($amount)) {
            throw new NotNormalizableValueException('The amount must be an integer amount in the currency\'s minor unit.', currentType: 'string', expectedTypes: ['int', 'string'], path: $this->appendPath($path, 'amount'), useMessageForUser: true);
        }

        try {
            return new Money($amount, new Currency($currency));
        } catch (\InvalidArgumentException $e) {
            throw new NotNormalizableValueException('The amount must be an integer amount in the currency\'s minor unit.', $e->getCode(), $e, get_debug_type($amount), ['int', 'string'], $this->appendPath($path, 'amount'), true);
        }
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return Money::class === $type;
    }

    /**
     * @return array<class-string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Money::class => true,
        ];
    }

    private function appendPath(?string $path, string $key): string
    {
        return null === $path || '' === $path ? $key : $path.'.'.$key;
    }
}
