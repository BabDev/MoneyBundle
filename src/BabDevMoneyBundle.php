<?php declare(strict_types=1);

namespace BabDev\MoneyBundle;

use BabDev\MoneyBundle\DependencyInjection\BabDevMoneyExtension;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BabDevMoneyBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register ODM mappings if DoctrineMongoDBBundle and the ODM are installed
        if (class_exists(DoctrineMongoDBBundle::class) && class_exists(DocumentManager::class)) {
            $container->addCompilerPass(DoctrineMongoDBMappingsPass::createXmlMappingDriver([realpath(__DIR__.'/../config/mapping') => 'Money'], []));
        }

        // Register ORM mappings if DoctrineBundle and the ORM are installed
        if (class_exists(DoctrineBundle::class) && class_exists(EntityManager::class)) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver([realpath(__DIR__.'/../config/mapping') => 'Money'], [], false, [], true));
        }
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (!isset($this->extension)) {
            $this->extension = new BabDevMoneyExtension();
        }

        return $this->extension ?: null;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
