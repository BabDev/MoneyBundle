<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
use JMS\SerializerBundle\JMSSerializerBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

final class BabDevMoneyExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoaded(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
            ]
        );

        $this->load();

        $this->assertContainerBuilderHasService('money.serializer.normalizer');
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

        $this->assertContainerBuilderHasService('money.serializer.handler');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new BabDevMoneyExtension(),
        ];
    }
}
