<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\DependencyInjection;

use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BabDevMoneyExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'babdev_money';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->setParameter('babdev_money.default_currency', $config['default_currency']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('money.php');

        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $loader->load('twig.php');
        }

        if (isset($bundles['JMSSerializerBundle'])) {
            $loader->load('jms_serializer.php');
        }

        if (interface_exists(FormInterface::class)) {
            $loader->load('form.php');
        }

        if (interface_exists(NormalizerInterface::class)) {
            $loader->load('serializer.php');
        }

        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('validator.php');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('doctrine') && class_exists(UnitOfWork::class)) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'BabDevMoneyBundle' => [
                                'type' => 'xml',
                                'prefix' => 'Money',
                                'dir' => '../config/doctrine',
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
