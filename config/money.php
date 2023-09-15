<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\MoneyBundle\Factory\FormatterFactory;
use BabDev\MoneyBundle\Factory\FormatterFactoryInterface;
use BabDev\MoneyBundle\Factory\ParserFactory;
use BabDev\MoneyBundle\Factory\ParserFactoryInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('money.factory.formatter', FormatterFactory::class)
            ->args([
                param('kernel.default_locale'),
            ])
        ->alias(FormatterFactoryInterface::class, 'money.factory.formatter')

        ->set('money.factory.parser', ParserFactory::class)
            ->args([
                param('kernel.default_locale'),
            ])
        ->alias(ParserFactoryInterface::class, 'money.factory.parser')
    ;
};
