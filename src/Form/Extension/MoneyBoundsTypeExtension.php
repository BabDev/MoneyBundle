<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Form\Extension;

use BabDev\MoneyBundle\Form\Type\MoneyType;
use Money\Money;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\BoundsType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Orders {@see Money} bounds when the {@see BoundsType} form type uses the {@see MoneyType} form type and its "compare" option is true.
 */
final class MoneyBoundsTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->addNormalizer(
            'compare',
            static function (Options $options, mixed $value): mixed {
                // A callable comparing the bounds, or a disabled comparison, is kept as is
                if (true !== $value || MoneyType::class !== $options['type']) {
                    return $value;
                }

                return static fn (Money $from, Money $to): int => $from->compare($to);
            },
        );
    }

    public static function getExtendedTypes(): iterable
    {
        return [BoundsType::class];
    }
}
