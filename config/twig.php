<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\MoneyBundle\Twig\MoneyExtension;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('money.twig_extension', MoneyExtension::class)
            ->args(
                [
                    service('money.factory.formatter'),
                    param('babdev_money.default_currency'),
                ]
            )
            ->tag('twig.extension')
    ;
};
