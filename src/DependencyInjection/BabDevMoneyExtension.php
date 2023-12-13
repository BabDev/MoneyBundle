<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\DependencyInjection;

use JMS\Serializer\SerializerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

final class BabDevMoneyExtension extends ConfigurableExtension
{
    public function getAlias(): string
    {
        return 'babdev_money';
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('babdev_money.default_currency', $mergedConfig['default_currency']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('money.php');

        if (ContainerBuilder::willBeAvailable('twig/twig', Environment::class, ['symfony/twig-bundle', 'babdev/money-bundle'])) {
            $loader->load('twig.php');
        }

        if (ContainerBuilder::willBeAvailable('jms/serializer', SerializerInterface::class, ['jms/serializer-bundle', 'babdev/money-bundle'])) {
            $loader->load('jms_serializer.php');
        }

        if (ContainerBuilder::willBeAvailable('symfony/form', FormInterface::class, ['babdev/money-bundle'])) {
            $loader->load('form.php');
        }

        if (ContainerBuilder::willBeAvailable('symfony/serializer', NormalizerInterface::class, ['babdev/money-bundle'])) {
            $loader->load('serializer.php');
        }

        if (ContainerBuilder::willBeAvailable('symfony/validator', ValidatorInterface::class, ['babdev/money-bundle'])) {
            $loader->load('validator.php');
        }
    }
}
