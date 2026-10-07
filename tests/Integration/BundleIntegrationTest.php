<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Integration;

use BabDev\MoneyBundle\Form\Type\MoneyType;
use BabDev\MoneyBundle\Tests\Integration\Fixtures\Invoice;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\SerializerBundle\JMSSerializerBundle;
use Money\Money;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

/**
 * Boots a kernel with the bundle and combinations of the bundles it integrates with.
 */
final class BundleIntegrationTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        new Filesystem()->remove(TestKernel::getTemporaryDirectory());
    }

    public static function tearDownAfterClass(): void
    {
        new Filesystem()->remove(TestKernel::getTemporaryDirectory());
    }

    /**
     * @param non-empty-string                       $scenario
     * @param list<class-string<BundleInterface>>    $bundles
     * @param array<string, array<array-key, mixed>> $config
     */
    private static function bootTestKernel(string $scenario, array $bundles = [], array $config = []): void
    {
        self::ensureKernelShutdown();

        $kernel = new TestKernel($scenario, $bundles, $config);
        $kernel->boot();

        self::$kernel = $kernel;
        self::$booted = true;
    }

    public function testServicesAreRegisteredWithTheFramework(): void
    {
        self::bootTestKernel('framework');

        $container = self::getContainer();

        $validator = $container->get(ValidatorInterface::class);

        self::assertInstanceOf(ValidatorInterface::class, $validator);
        self::assertCount(1, $validator->validate(new Invoice(Money::USD(-100))));

        $formFactory = $container->get('test.form.factory');

        self::assertInstanceOf(FormFactoryInterface::class, $formFactory);
        self::assertInstanceOf(MoneyType::class, $formFactory->create(MoneyType::class)->getConfig()->getType()->getInnerType());

        // Denormalization is checked as the JsonSerializable normalizer would normalize a Money instance the same way
        $denormalizer = $container->get('serializer');

        self::assertInstanceOf(DenormalizerInterface::class, $denormalizer);
        self::assertEquals(Money::USD(100), $denormalizer->denormalize(['amount' => '100', 'currency' => 'USD'], Money::class));
    }

    public function testValidatorsWorkWithoutThePropertyAccessor(): void
    {
        self::bootTestKernel('without_property_access', [], [
            // The form and serializer components require the property accessor
            'framework' => [
                'form' => ['enabled' => false],
                'property_access' => ['enabled' => false],
                'serializer' => ['enabled' => false],
            ],
        ]);

        $validator = self::getContainer()->get(ValidatorInterface::class);

        self::assertInstanceOf(ValidatorInterface::class, $validator);
        self::assertCount(1, $validator->validate(new Invoice(Money::USD(-100))));
    }

    public function testTwigExtensionIsRegistered(): void
    {
        self::bootTestKernel('twig', [TwigBundle::class]);

        $twig = self::getContainer()->get('twig');

        self::assertInstanceOf(Environment::class, $twig);
        self::assertNotNull($twig->getFilter('money'));
        self::assertNotNull($twig->getFunction('money'));
    }

    public function testDoctrineBundleWithoutTheOrmConfigured(): void
    {
        self::bootTestKernel(
            'doctrine_dbal',
            [DoctrineBundle::class],
            ['doctrine' => ['dbal' => ['url' => 'sqlite:///:memory:']]],
        );

        self::assertFalse(self::getContainer()->has('doctrine.orm.default_entity_manager'));
    }

    #[RequiresPhpExtension('pdo_sqlite')]
    public function testDoctrineOrmIntegration(): void
    {
        self::bootTestKernel(
            'doctrine_orm',
            [DoctrineBundle::class],
            [
                'doctrine' => [
                    'dbal' => ['url' => 'sqlite:///:memory:'],
                    'orm' => [
                        'mappings' => [
                            'Fixtures' => [
                                'type' => 'attribute',
                                'dir' => __DIR__.'/Fixtures',
                                'prefix' => 'BabDev\MoneyBundle\Tests\Integration\Fixtures',
                                'is_bundle' => false,
                            ],
                        ],
                    ],
                ],
            ],
        );

        $entityManager = self::getContainer()->get('doctrine.orm.default_entity_manager');

        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        new SchemaTool($entityManager)->createSchema([$entityManager->getClassMetadata(Invoice::class)]);

        $invoice = new Invoice(Money::JPY(1234));

        $entityManager->persist($invoice);
        $entityManager->flush();
        $entityManager->clear();

        $loaded = $entityManager->find(Invoice::class, $invoice->id);

        self::assertInstanceOf(Invoice::class, $loaded);
        self::assertEquals(Money::JPY(1234), $loaded->total);
    }

    #[RequiresPhpExtension('mongodb')]
    public function testDoctrineMongoDbOdmIntegration(): void
    {
        self::bootTestKernel(
            'doctrine_mongodb',
            [DoctrineMongoDBBundle::class],
            [
                'doctrine_mongodb' => [
                    'connections' => ['default' => ['server' => 'mongodb://localhost:27017']],
                    'document_managers' => ['default' => ['auto_mapping' => false]],
                ],
            ],
        );

        self::assertTrue(self::getContainer()->has('doctrine_mongodb.odm.default_document_manager'));
    }

    public function testJmsSerializerHandlerIsRegistered(): void
    {
        self::bootTestKernel('jms_serializer', [JMSSerializerBundle::class]);

        $handlerRegistry = self::getContainer()->get('jms_serializer.handler_registry');

        self::assertInstanceOf(HandlerRegistryInterface::class, $handlerRegistry);

        foreach ([GraphNavigatorInterface::DIRECTION_SERIALIZATION, GraphNavigatorInterface::DIRECTION_DESERIALIZATION] as $direction) {
            foreach (['json', 'xml'] as $format) {
                self::assertNotNull($handlerRegistry->getHandler($direction, Money::class, $format));
            }
        }
    }
}
