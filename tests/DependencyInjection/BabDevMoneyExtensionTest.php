<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
use Composer\InstalledVersions;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\DefinitionDecoratesConstraint;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class BabDevMoneyExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoadedWithDefaultConfiguration(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'USD');
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.validator.greater_than');

        if (class_exists(InstalledVersions::class)) {
            $version = InstalledVersions::getVersion('symfony/serializer');

            if (null !== $version && version_compare($version, '6.3', '<')) {
                // TODO - Fix upstream
                // $this->assertContainerBuilderServiceDecoration('money.serializer.normalizer.legacy', 'money.serializer.normalizer');
                self::assertThat($this->container, new DefinitionDecoratesConstraint('money.serializer.normalizer.legacy', 'money.serializer.normalizer'));
            } else {
                $this->assertContainerBuilderNotHasService('money.serializer.normalizer.legacy');
            }
        }
    }

    public function testContainerIsLoadedWithCustomConfiguration(): void
    {
        $this->load(['default_currency' => 'EUR']);

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'EUR');
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.validator.greater_than');
    }

    public function testContainerIsLoadedWhenJMSSerializerBundleIsInstalled(): void
    {
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
        $this->load();

        $this->assertContainerBuilderHasParameter('babdev_money.default_currency', 'USD');
        $this->assertContainerBuilderHasService('money.factory.formatter');
        $this->assertContainerBuilderHasService('money.form.type.money');
        $this->assertContainerBuilderHasService('money.serializer.normalizer');
        $this->assertContainerBuilderHasService('money.twig_extension');
        $this->assertContainerBuilderHasService('money.validator.greater_than');
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions(): array
    {
        return [
            new BabDevMoneyExtension(),
        ];
    }
}
