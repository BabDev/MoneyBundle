<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\MoneyBundle\Form\Extension\MoneyBoundsTypeExtension;
use BabDev\MoneyBundle\Form\MoneyTypeGuesser;
use BabDev\MoneyBundle\Form\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\BoundsType;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('money.form.type.money', MoneyType::class)
            ->args([
                service('money.factory.formatter'),
                service('money.factory.parser'),
                param('babdev_money.default_currency'),
                service('money.currencies'),
            ])
            ->tag('form.type')

        ->set('money.form.type_guesser', MoneyTypeGuesser::class)
            ->tag('form.type_guesser')
    ;

    // The BoundsType form type was added in Symfony 8.2
    if (class_exists(BoundsType::class)) {
        $container->services()
            ->set('money.form.type_extension.bounds', MoneyBoundsTypeExtension::class)
                ->tag('form.type_extension')
        ;
    }
};
