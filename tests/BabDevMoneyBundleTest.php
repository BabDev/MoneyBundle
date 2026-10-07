<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BabDevMoneyBundleTest extends TestCase
{
    /**
     * @return \Generator<string, array{class-string, class-string, non-empty-string, non-empty-string}>
     */
    public static function provideDoctrineMappings(): \Generator
    {
        yield 'Doctrine ORM' => [DoctrineBundle::class, EntityManager::class, 'doctrine.default_entity_manager', 'doctrine.orm.default_metadata_driver'];
        yield 'Doctrine MongoDB ODM' => [DoctrineMongoDBBundle::class, DocumentManager::class, 'doctrine_mongodb.odm.default_document_manager', 'doctrine_mongodb.odm.default_metadata_driver'];
    }

    /**
     * @param class-string     $bundleClass
     * @param class-string     $managerClass
     * @param non-empty-string $chainDriverId
     */
    #[DataProvider('provideDoctrineMappings')]
    public function testMappingsAreNotRegisteredWhenTheDoctrineManagerIsNotConfigured(string $bundleClass, string $managerClass, string $managerParameter, string $chainDriverId): void
    {
        $this->skipIfMissing($bundleClass, $managerClass);

        $container = new ContainerBuilder();
        $container->register($chainDriverId);

        $this->processMappingPasses($container);

        self::assertFalse($container->getDefinition($chainDriverId)->hasMethodCall('addDriver'));
    }

    /**
     * @param class-string     $bundleClass
     * @param class-string     $managerClass
     * @param non-empty-string $managerParameter
     * @param non-empty-string $chainDriverId
     */
    #[DataProvider('provideDoctrineMappings')]
    public function testMappingsAreRegisteredWhenTheDoctrineManagerIsConfigured(string $bundleClass, string $managerClass, string $managerParameter, string $chainDriverId): void
    {
        $this->skipIfMissing($bundleClass, $managerClass);

        $container = new ContainerBuilder();
        $container->setParameter($managerParameter, 'default');
        $container->register($chainDriverId);

        $this->processMappingPasses($container);

        $chainDriver = $container->getDefinition($chainDriverId);

        self::assertTrue($chainDriver->hasMethodCall('addDriver'));

        // Each method call is a [method, arguments] pair, and the "addDriver" arguments are [driver, namespace]
        self::assertContains('Money', array_column(array_column($chainDriver->getMethodCalls(), 1), 1));
    }

    /**
     * @param class-string $bundleClass
     * @param class-string $managerClass
     */
    private function skipIfMissing(string $bundleClass, string $managerClass): void
    {
        if (!class_exists($bundleClass) || !class_exists($managerClass)) {
            self::markTestSkipped(\sprintf('Test requires "%s" and "%s".', $bundleClass, $managerClass));
        }
    }

    private function processMappingPasses(ContainerBuilder $container): void
    {
        new BabDevMoneyBundle()->build($container);

        foreach ($container->getCompilerPassConfig()->getBeforeOptimizationPasses() as $pass) {
            if ($pass instanceof RegisterMappingsPass) {
                $pass->process($container);
            }
        }
    }
}
