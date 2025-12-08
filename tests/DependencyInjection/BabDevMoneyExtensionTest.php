<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class BabDevMoneyExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoadedWithDefaultConfiguration(): void
    {
        $this->container->setParameter('kernel.environment', 'dev');
        $this->container->setParameter('kernel.build_dir', __DIR__);

        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
            ],
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
        $this->container->setParameter('kernel.environment', 'dev');
        $this->container->setParameter('kernel.build_dir', __DIR__);

        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
            ],
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
        if (!class_exists(JMSSerializerBundle::class)) {
            self::markTestSkipped('Test requires JMSSerializerBundle');
        }

        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
                'JMSSerializerBundle' => JMSSerializerBundle::class,
            ],
        );

        $this->container->setParameter('kernel.environment', 'dev');
        $this->container->setParameter('kernel.build_dir', __DIR__);

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
        $this->container->setParameter('kernel.environment', 'dev');
        $this->container->setParameter('kernel.build_dir', __DIR__);

        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
            ],
        );

        $this->load();

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'USD');
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.twig_extension');
        $this->assertContainerBuilderHasService('money.validator.greater_than');
    }

    /**
     * @return list<ExtensionInterface>
     */
    protected function getContainerExtensions(): array
    {
        $extension = new BabDevMoneyBundle()->getContainerExtension();

        if (!$extension instanceof ExtensionInterface) {
            throw new \RuntimeException('The container extension could not be retrieved from the bundle.');
        }

        return [
            $extension,
        ];
    }
}
