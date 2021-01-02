# Installation & Setup

To install this bundle, run the following [Composer](https://getcomposer.org/) command:

```bash
composer require babdev/money-bundle
```

## Register The Bundle

### Symfony Flex

For an application using Symfony Flex the bundle should be automatically registered, but if not you will need to add it to your `config/bundles.php` file.

```php
<?php

return [
    // ...

    BabDev\MoneyBundle\BabDevMoneyBundle::class => ['all' => true],
];
```

### Symfony Standard

For an application based on the Symfony Standard structure, you will need to add the bundle to your `AppKernel` class' `registerBundles()` method.

```php
<?php
// app/AppKernel.php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...

            new BabDev\MoneyBundle\BabDevMoneyBundle(),
        ];

        // ...
    }

    // ...
}
```
