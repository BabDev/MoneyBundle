<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\MoneyBundle\Serializer\Normalizer\MoneyNormalizer;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('money.serializer.normalizer', MoneyNormalizer::class)
            ->tag('serializer.normalizer')
    ;
};
