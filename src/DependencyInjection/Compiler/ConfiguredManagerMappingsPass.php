<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\DependencyInjection\Compiler;

use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterMappingsPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers Doctrine mappings only when the default manager is configured.
 *
 * @internal
 */
final readonly class ConfiguredManagerMappingsPass implements CompilerPassInterface
{
    public function __construct(
        private RegisterMappingsPass $pass,
        private string $managerParameter,
    ) {}

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter($this->managerParameter)) {
            return;
        }

        $managerName = $container->getParameter($this->managerParameter);

        if (!\is_string($managerName) || '' === $managerName) {
            return;
        }

        $this->pass->process($container);
    }
}
