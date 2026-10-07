# Validator

The MoneyBundle provides support for making a number of comparisons for `Money\Money` instances using the [Symfony Validator component](https://symfony.com/doc/current/components/validator.html).

## Available Constraints

All constraints support either a `Money\Money` instance or a scalar value (string/int/float) which can be parsed into a `Money\Money` instance.

### `MoneyEqualTo`

Validates that a value is equal to another value as defined in the options. To validate that a value is not equal, see `MoneyNotEqualTo`.

### `MoneyNotEqualTo`

Validates that a value is not equal to another value as defined in the options. To validate that a value is equal, see `MoneyEqualTo`.

### `MoneyLessThan`

Validates that a value is less than another value as defined in the options. To validate that a value is less than or equal to another value, see `MoneyLessThanOrEqual`. To validate a value is greater than another value, see `MoneyGreaterThan`.

### `MoneyLessThanOrEqual`

Validates that a value is less than or equal to another value as defined in the options. To validate that a value is less than another value, see `MoneyLessThan`.

### `MoneyGreaterThan`

Validates that a value is greater than another value as defined in the options. To validate that a value is greater than or equal to another value, see `MoneyGreaterThanOrEqual`. To validate a value is less than another value, see `MoneyLessThan`.

### `MoneyGreaterThanOrEqual`

Validates that a value is greater than or equal to another value as defined in the options. To validate that a value is greater than another value, see `MoneyGreaterThan`.

## Constraint Options

The constraints support the following extra options, similar to the comparison constraints provided by the Validator component:

- `currencyMismatchMessage` - This is the message that will be shown if the value and the compared value have different currencies, and supports the same parameters as the `message` option
- `groups` - Defines the validation group(s) this constraint belongs to
- `message` - This is the message that will be shown if the value fails the validation check; messages have the following parameters available:
    - `{{ compared_value }}` - The value being compared to
    - `{{ compared_value_type }}` - The expected value type
    - `{{ value }}` - The current (invalid) value
- `payload` - This option can be used to attach arbitrary domain-specific data to a constraint, it is not used by the Validator component, but its processing is completely up to you
- `propertyPath` - Defines the object property whose value is used to make the comparison
- `value` - This option is required; it defines the value to compare to, this should be a `Money\Money` instance or a scalar value (string/int/float) that can be parsed into a `Money\Money` instance

The constraints also support the following options to control how values are converted and displayed:

- `currency` - The currency code used when converting a scalar value into a `Money\Money` instance; defaults to the currency of the other value if it is a `Money\Money` instance, otherwise the `babdev_money.default_currency` configuration value
- `formatterFormat` - The format used to display values in violation messages, as one of the `BabDev\MoneyBundle\Format` constants; defaults to `Format::INTL_MONEY`
- `parserFormat` - The format used to parse a scalar value into a `Money\Money` instance, as one of the `BabDev\MoneyBundle\Format` constants; defaults to `Format::DECIMAL`
- `fractionDigits` - The number of fraction digits used by the intl formatters and parsers; defaults to 2
- `groupingUsed` - Whether the intl formatters and parsers use grouping separators; defaults to true
- `locale` - The locale used by the intl formatters and parsers; defaults to the `kernel.default_locale` parameter
- `style` - The number style used by the intl formatters and parsers, either `currency` or `decimal`; defaults to `currency`

```php
#[MoneyAssert\MoneyGreaterThanOrEqual(value: '10.00', currency: 'EUR', locale: 'de')]
public Money $price;
```

## Currencies

When a scalar value is converted into a `Money\Money` instance for comparison, it uses the constraint's `currency` option if set. Otherwise, it uses the currency of the other value when that value is a `Money\Money` instance, so a constraint such as `#[MoneyGreaterThan(value: 0)]` works with values in any currency. If neither value is a `Money\Money` instance, the `babdev_money.default_currency` configuration value is used.

Values with different currencies cannot be ordered, so the `MoneyGreaterThan`, `MoneyGreaterThanOrEqual`, `MoneyLessThan`, and `MoneyLessThanOrEqual` constraints add a violation using the `currencyMismatchMessage` option, with the `AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR` code, instead of comparing them. For the `MoneyEqualTo` and `MoneyNotEqualTo` constraints, values with different currencies are not equal.

## Translations

The default messages for the constraints are translated in the `validators` domain. The `message` defaults reuse the wording of the comparison constraints from the Validator component, so they are translated by the Validator component's own translations. The bundle provides an English translation for the `currencyMismatchMessage` default; to translate it into other languages, add the message to your application's `validators` translation files:

```yaml
# translations/validators.de.yaml
'This value should be in the same currency as {{ compared_value }}.': 'Dieser Wert sollte dieselbe Währung wie {{ compared_value }} haben.'
```

## Form Support

When used alongside the [Symfony Form component](https://symfony.com/doc/current/components/form.html), the constraints can be used with your forms to validate your data.

## Examples

### Attributes

```php
<?php

namespace App\Entity;

use BabDev\MoneyBundle\Validator\Constraints as MoneyAssert;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

#[ORM\Entity]
class Invoice
{
    #[ORM\Embedded(class: Money::class]
    #[MoneyAssert\MoneyGreaterThanOrEqual(value: 0)]
    public Money $tax_due;

    public function __construct()
    {
        $this->tax_due = Money::USD(0);
    }
}
```

### PHP

```php
<?php

namespace App\Model;

use BabDev\MoneyBundle\Validator\Constraints as MoneyAssert;
use Money\Money;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Invoice
{
    public Money $tax_due;

    public function __construct()
    {
        $this->tax_due = Money::USD(0);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'tax_due',
            new MoneyAssert\MoneyGreaterThanOrEqual(
                value: 0,
            ),
        );
    }
}
```

### XML

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<constraint-mapping xmlns="http://symfony.com/schema/dic/constraint-mapping"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://symfony.com/schema/dic/constraint-mapping https://symfony.com/schema/dic/constraint-mapping/constraint-mapping-1.0.xsd">

    <class name="App\Entity\Invoice">
        <property name="tax_due">
            <constraint name="MoneyGreaterThanOrEqual">
                <option name="value">0</option>
            </constraint>
        </property>
    </class>
</constraint-mapping>
```

### YAML

```yaml
App\Entity\Invoice:
    properties:
        tax_due:
            - MoneyGreaterThanOrEqual:
                value: 0
```
