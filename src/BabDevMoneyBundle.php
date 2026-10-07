<?php declare(strict_types=1);

namespace BabDev\MoneyBundle;

use BabDev\MoneyBundle\DependencyInjection\Compiler\ConfiguredManagerMappingsPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

final class BabDevMoneyBundle extends AbstractBundle
{
    protected string $extensionAlias = 'babdev_money';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register ODM mappings if DoctrineMongoDBBundle and the ODM are installed
        if (class_exists(DoctrineMongoDBBundle::class) && class_exists(DocumentManager::class)) {
            $container->addCompilerPass(new ConfiguredManagerMappingsPass(DoctrineMongoDBMappingsPass::createXmlMappingDriver([realpath(__DIR__.'/../config/mapping') => 'Money'], [], 'doctrine_mongodb.odm.default_document_manager'), 'doctrine_mongodb.odm.default_document_manager'));
        }

        // Register ORM mappings if DoctrineBundle and the ORM are installed
        if (class_exists(DoctrineBundle::class) && class_exists(EntityManager::class)) {
            /*
             * The $aliasMap parameter is deprecated in DoctrineBundle 3.2 and was removed from the signature using a
             * dynamic B/C layer. If the parameter still exists, we are on a version of the bundle that requires an array;
             * if it doesn't, the $enableXsdValidation parameter is now in that position.
             */
            if ('aliasMap' === new \ReflectionClass(DoctrineOrmMappingsPass::class)->getMethod('createXmlMappingDriver')->getParameters()[3]->getName()) {
                $container->addCompilerPass(new ConfiguredManagerMappingsPass(DoctrineOrmMappingsPass::createXmlMappingDriver([realpath(__DIR__.'/../config/mapping') => 'Money'], [], 'doctrine.default_entity_manager', [], true), 'doctrine.default_entity_manager'));
            } else {
                $container->addCompilerPass(new ConfiguredManagerMappingsPass(DoctrineOrmMappingsPass::createXmlMappingDriver([realpath(__DIR__.'/../config/mapping') => 'Money'], [], 'doctrine.default_entity_manager', true), 'doctrine.default_entity_manager'));
            }
        }
    }

    /**
     * @param DefinitionConfigurator<'array'> $definition
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->stringNode('default_currency')
                    ->info('The currency code used when no currency is given, which must be supported by the "money.currencies" service to be used with the form type.')
                    ->defaultValue('USD')
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array{default_currency: non-empty-string} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()
            ->set('babdev_money.default_currency', $config['default_currency']);

        $container->import('../config/money.php');

        if (ContainerBuilder::willBeAvailable('twig/twig', Environment::class, ['symfony/twig-bundle', 'babdev/money-bundle'])) {
            $container->import('../config/twig.php');
        }

        if (ContainerBuilder::willBeAvailable('jms/serializer', SerializerInterface::class, ['jms/serializer-bundle', 'babdev/money-bundle'])) {
            $container->import('../config/jms_serializer.php');
        }

        if (ContainerBuilder::willBeAvailable('symfony/form', FormInterface::class, ['babdev/money-bundle'])) {
            $container->import('../config/form.php');
        }

        if (ContainerBuilder::willBeAvailable('symfony/serializer', NormalizerInterface::class, ['babdev/money-bundle'])) {
            $container->import('../config/serializer.php');
        }

        if (ContainerBuilder::willBeAvailable('symfony/validator', ValidatorInterface::class, ['babdev/money-bundle'])) {
            $container->import('../config/validator.php');
        }
    }
}
