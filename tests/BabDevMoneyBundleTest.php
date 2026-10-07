<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use BabDev\MoneyBundle\DependencyInjection\Compiler\ConfiguredManagerMappingsPass;
use BabDev\MoneyBundle\Validator\Constraints\AbstractMoneyComparisonValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThanValidator;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
     * DoctrineBundle defines the "doctrine.default_entity_manager" parameter as an empty string when only the DBAL is configured.
     *
     * @param class-string     $bundleClass
     * @param class-string     $managerClass
     * @param non-empty-string $managerParameter
     * @param non-empty-string $chainDriverId
     */
    #[DataProvider('provideDoctrineMappings')]
    public function testMappingsAreNotRegisteredWhenTheDoctrineManagerParameterIsEmpty(string $bundleClass, string $managerClass, string $managerParameter, string $chainDriverId): void
    {
        $this->skipIfMissing($bundleClass, $managerClass);

        $container = new ContainerBuilder();
        $container->setParameter($managerParameter, '');
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

    public function testValidatorsCanBeCreatedWithoutThePropertyAccessor(): void
    {
        $container = $this->compileContainer();

        $validator = $container->get('money.validator.greater_than');

        self::assertInstanceOf(MoneyGreaterThanValidator::class, $validator);
        self::assertNull(new \ReflectionProperty(AbstractMoneyComparisonValidator::class, 'propertyAccessor')->getValue($validator));
    }

    public function testValidatorsAreCreatedWithThePropertyAccessorWhenAvailable(): void
    {
        $container = $this->compileContainer(static function (ContainerBuilder $container): void {
            $container->register('property_accessor', PropertyAccessor::class)->setPublic(true);
        });

        $validator = $container->get('money.validator.greater_than');

        self::assertInstanceOf(MoneyGreaterThanValidator::class, $validator);
        self::assertSame($container->get('property_accessor'), new \ReflectionProperty(AbstractMoneyComparisonValidator::class, 'propertyAccessor')->getValue($validator));
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
            if ($pass instanceof ConfiguredManagerMappingsPass) {
                $pass->process($container);
            }
        }
    }

    /**
     * @param (\Closure(ContainerBuilder): void)|null $configure
     */
    private function compileContainer(?\Closure $configure = null): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        $bundle = new BabDevMoneyBundle();
        $extension = $bundle->getContainerExtension();

        self::assertNotNull($extension);

        $container->registerExtension($extension);
        $container->loadFromExtension($extension->getAlias(), []);

        $bundle->build($container);

        if (null !== $configure) {
            $configure($container);
        }

        // Validators are private services only referenced by the validator's service locator, make one public so it is kept and checked during compilation
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->getDefinition('money.validator.greater_than')->setPublic(true);
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);

        $container->compile();

        return $container;
    }
}
