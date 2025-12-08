# Configuring The Bundle

## Default Currency

The default currency for your application can be set with the `default_currency` configuration node. This defaults to "USD" and can be any valid currency code accepted by the Money library.

### YAML based configuration

```yaml
# config/packages/babdev_money.yaml
babdev_money:
    default_currency: EUR
```

### PHP based configuration

```php
<?php // config/packages/babdev_money.php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'babdev_money' => [
        'default_currency' => 'EUR',
    ],
]);
```
