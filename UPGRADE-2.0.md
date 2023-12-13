# Upgrade from 1.x to 2.0

The below guide will assist in upgrading from the 1.x versions to 2.0.

## Bundle Requirements

- Symfony 6.4 or 7.0+
- PHP 8.2 or later
- `moneyphp/money` 4.0 or later
- `jms/serializer-bundle` 5.0 or later

## General Changes

- The constructor for `BabDev\MoneyBundle\Form\DataTransformer\MoneyToLocalizedStringTransformer` now requires a `Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer` as its fourth parameter

## Removed Features

- The validation constraints no longer support annotations, use attributes instead
