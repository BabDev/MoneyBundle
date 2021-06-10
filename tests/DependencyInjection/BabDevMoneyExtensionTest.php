<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
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
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.validator.greater_than');
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
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.validator.greater_than');
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
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.handler');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.validator.greater_than');
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
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.twig_extension');
        $this->assertContainerBuilderHasService('money.validator.greater_than');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new BabDevMoneyExtension(),
        ];
    }
}
