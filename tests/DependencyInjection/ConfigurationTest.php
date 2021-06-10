<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertEquals(self::getBundleDefaultConfig(), $config);
    }

    public function testConfigWithCustomDefaultCurrency(): void
    {
        $extraConfig = [
            'default_currency' => 'EUR',
        ];

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    protected static function getBundleDefaultConfig(): array
    {
        return [
            'default_currency' => 'USD',
        ];
    }
}
