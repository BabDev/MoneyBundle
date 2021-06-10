<?php declare(strict_types=1);

namespace BabDev\MoneyBundle;

use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BabDevMoneyBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register ORM mappings if DoctrineBundle and the ORM are installed
        if (class_exists(DoctrineBundle::class) && class_exists(UnitOfWork::class)) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver([realpath(__DIR__.'../config/doctrine') => 'Money']));
        }
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new BabDevMoneyExtension();
        }

        return $this->extension ?: null;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
