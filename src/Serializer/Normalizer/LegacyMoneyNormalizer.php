<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Serializer\Normalizer;

use Money\Money;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Decorator for {@see MoneyNormalizer} implementing the legacy {@see CacheableSupportsMethodInterface} for older Symfony version support.
 *
 * @internal
 */
final class LegacyMoneyNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(private readonly MoneyNormalizer $normalizer) {}

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $this->normalizer->supportsNormalization($data, $format, $context);
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): Money
    {
        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->normalizer->supportsDenormalization($data, $type, $format, $context);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
