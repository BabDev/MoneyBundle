# Configuring The Bundle

## Default Currency

The default currency for your application can be set with the `default_currency` configuration node. This defaults to "USD" and can be any valid currency code accepted by the Money library.

```yaml
# app/config/config.yml for Symfony Standard applications
# config/packages/babdev_money.yaml for Symfony Flex applications
babdev_money:
    default_currency: EUR
```
