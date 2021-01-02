<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\DependencyInjection;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

final class BabDevMoneyExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testContainerIsLoaded(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevMoneyBundle' => BabDevMoneyBundle::class,
            ]
        );

        $this->load();
    }

    protected function getContainerExtensions(): array
    {
        return [
            new BabDevMoneyExtension(),
        ];
    }
}
