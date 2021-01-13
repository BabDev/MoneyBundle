<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\MoneyBundle\Validator\Constraints\MoneyEqualToValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThanOrEqualValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThanValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThanOrEqualValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyLessThanValidator;
use BabDev\MoneyBundle\Validator\Constraints\MoneyNotEqualToValidator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('money.validator.abstract')
            ->abstract()
            ->args(
                [
                    service('money.factory.formatter'),
                    service('money.factory.parser'),
                    param('babdev_money.default_currency'),
                    service('property_accessor'),
                ]
            )

        ->set('money.validator.equal_to', MoneyEqualToValidator::class)
            ->parent('money.validator.abstract')
            ->tag('validator.constraint_validator', ['alias' => MoneyEqualToValidator::class])

        ->set('money.validator.not_equal_to', MoneyNotEqualToValidator::class)
            ->parent('money.validator.abstract')
            ->tag('validator.constraint_validator', ['alias' => MoneyNotEqualToValidator::class])

        ->set('money.validator.greater_than', MoneyGreaterThanValidator::class)
            ->parent('money.validator.abstract')
            ->tag('validator.constraint_validator', ['alias' => MoneyGreaterThanValidator::class])

        ->set('money.validator.greater_than_or_equal', MoneyGreaterThanOrEqualValidator::class)
            ->parent('money.validator.abstract')
            ->tag('validator.constraint_validator', ['alias' => MoneyGreaterThanOrEqualValidator::class])

        ->set('money.validator.less_than', MoneyLessThanValidator::class)
            ->parent('money.validator.abstract')
            ->tag('validator.constraint_validator', ['alias' => MoneyLessThanValidator::class])

        ->set('money.validator.less_than_or_equal', MoneyLessThanOrEqualValidator::class)
            ->parent('money.validator.abstract')
            ->tag('validator.constraint_validator', ['alias' => MoneyLessThanOrEqualValidator::class])
    ;
};
