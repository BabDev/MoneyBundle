<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;

final class BabDevMoneyExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoadedWithDefaultConfiguration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
            ]
        );

        $this->load();

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'USD');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
    }

    public function testContainerIsLoadedWithCustomConfiguration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
            ]
        );

        $this->load(['default_currency' => 'EUR']);

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'EUR');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
    }

    public function testContainerIsLoadedWhenDoctrineBundleIsInstalled(): void
    {
        $this->container->registerExtension(new DoctrineExtension());

        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
                'DoctrineBundle' => DoctrineBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', false);

        $this->load();

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'USD');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');

        $doctrineConfig = $this->container->getExtensionConfig('doctrine');

        $this->assertArrayHasKey('BabDevMoneyBundle', $doctrineConfig[0]['orm']['mappings']);
    }

    public function testContainerIsLoadedWhenJMSSerializerBundleIsInstalled(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
                'JMSSerializerBundle' => JMSSerializerBundle::class,
            ]
        );

        $this->load();

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'USD');
        $this->assertContainerBuilderHasService('money.serializer.handler');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
    }

    public function testContainerIsLoadedWhenTwigBundleIsInstalled(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
                'TwigBundle' => TwigBundle::class,
            ]
        );

        $this->load();

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'USD');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.twig_extension');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new BabDevMoneyExtension(),
        ];
    }
}
