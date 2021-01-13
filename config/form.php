<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\MoneyBundle\Form\Type\MoneyType;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('money.form.type.money', MoneyType::class)
            ->args(
                [
                    service('money.factory.formatter'),
                    service('money.factory.parser'),
                    param('babdev_money.default_currency'),
                ]
            )
            ->tag('form.type')
    ;
};
