<?php declare(strict_types=1);

namespace BabDev\MoneyBundle\Tests\Integration;

use BabDev\MoneyBundle\BabDevMoneyBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Kernel for testing the bundle with a combination of other bundles.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param non-empty-string                       $scenario        A name for the combination of bundles, used to separate the cache of each combination
     * @param list<class-string<BundleInterface>>    $extraBundles    The bundles to register in addition to the FrameworkBundle and this bundle
     * @param array<string, array<array-key, mixed>> $extensionConfig The configuration for the extra bundles, keyed by extension alias
     */
    public function __construct(
        private readonly string $scenario,
        private readonly array $extraBundles = [],
        private readonly array $extensionConfig = [],
    ) {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new BabDevMoneyBundle();

        foreach ($this->extraBundles as $bundleClass) {
            yield new $bundleClass();
        }
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return self::getTemporaryDirectory().'/'.$this->scenario.'/cache';
    }

    public function getLogDir(): string
    {
        return self::getTemporaryDirectory().'/'.$this->scenario.'/log';
    }

    public static function getTemporaryDirectory(): string
    {
        return sys_get_temp_dir().'/babdev_money_bundle_tests';
    }

    protected function build(ContainerBuilder $container): void
    {
        // Keep the form factory when forms are enabled, which would otherwise be removed as no service in this kernel uses it
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                if ($container->has('form.factory')) {
                    $container->setAlias('test.form.factory', 'form.factory')->setPublic(true);
                }
            }
        });
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'form' => ['enabled' => true],
            'property_access' => ['enabled' => true],
            'serializer' => ['enabled' => true],
            'validation' => ['enabled' => true],
        ]);

        // Discard log messages, which the default logger would write to stderr
        $container->services()->set('logger', NullLogger::class);

        foreach ($this->extensionConfig as $alias => $config) {
            $container->extension($alias, $config);
        }
    }
}
