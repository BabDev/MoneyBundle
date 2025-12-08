<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleExtension;

final class ConfigurationTest extends TestCase
{
    protected function getConfiguration(): ConfigurationInterface
    {
        $extension = new BabDevMoneyBundle()->getContainerExtension();

        if (!$extension instanceof BundleExtension) {
            throw new \RuntimeException('The container extension could not be retrieved from the bundle.');
        }

        return $extension->getConfiguration([], new ContainerBuilder()) ?? throw new \RuntimeException('The configuration could not be retrieved from the container extension.');
    }

    public function testDefaultConfig(): void
    {
        $config = new Processor()->processConfiguration($this->getConfiguration(), []);

        self::assertEquals(self::getBundleDefaultConfig(), $config);
    }

    public function testConfigWithCustomDefaultCurrency(): void
    {
        $extraConfig = [
            'default_currency' => 'EUR',
        ];

        $config = new Processor()->processConfiguration($this->getConfiguration(), [$extraConfig]);

        self::assertEquals(array_merge(self::getBundleDefaultConfig(), $extraConfig), $config);
    }

    protected static function getBundleDefaultConfig(): array
    {
        return [
            'default_currency' => 'USD',
        ];
    }
}
