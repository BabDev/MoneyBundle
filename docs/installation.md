# Installation & Setup

To install this bundle, run the following [Composer](https://getcomposer.org/) command:

```bash
composer require babdev/money-bundle
```

## Register The Bundle

For an application using Symfony Flex the bundle should be automatically registered, but if not you will need to add it to your `config/bundles.php` file.

```php
<?php

return [
    // ...

    BabDev\MoneyBundle\BabDevMoneyBundle::class => ['all' => true],
];
```
