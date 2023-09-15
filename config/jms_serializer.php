<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\MoneyBundle\Serializer\Handler\MoneyHandler;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('money.serializer.handler', MoneyHandler::class)
            ->tag('jms_serializer.subscribing_handler')
    ;
};
