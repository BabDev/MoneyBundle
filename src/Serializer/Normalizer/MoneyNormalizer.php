<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Serializer\Normalizer;

use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class MoneyNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    /**
     * @param mixed $object Object to normalize
     *
     * @throws InvalidArgumentException when the object given is not a supported type for the normalizer
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Money) {
            throw new InvalidArgumentException(sprintf('The object must be an instance of "%s".', Money::class));
        }

        return [
            'amount' => $object->getAmount(),
            'currency' => $object->getCurrency()->getCode(),
        ];
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Money;
    }

    /**
     * @param mixed $data Data to restore
     *
     * @throws InvalidArgumentException Occurs when the arguments are not coherent or not supported
     * @throws UnexpectedValueException Occurs when the item cannot be hydrated with the given data
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): Money
    {
        if (!\is_array($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be an array, "%s" given.', get_debug_type($data)));
        }

        if (!isset($data['amount']) || !isset($data['currency'])) {
            throw new UnexpectedValueException('Missing required keys from data array, must provide "amount" and "currency".');
        }

        try {
            return new Money($data['amount'], new Currency($data['currency']));
        } catch (\Exception $e) {
            throw new NotNormalizableValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
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

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
