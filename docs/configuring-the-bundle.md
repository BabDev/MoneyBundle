# Configuring The Bundle

## Default Currency

The default currency for your application can be set with the `default_currency` configuration node. This defaults to "USD" and can be any valid currency code accepted by the Money library.

```yaml
# config/packages/babdev_money.yaml
babdev_money:
    default_currency: EUR
```
