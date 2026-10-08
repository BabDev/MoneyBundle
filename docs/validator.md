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

### `MoneyPositive`, `MoneyPositiveOrZero`, `MoneyNegative`, and `MoneyNegativeOrZero`

Validates that a value is greater than zero, greater than or equal to zero, less than zero, or less than or equal to zero. Zero is compared in the currency of the validated value, so these constraints work with values in any currency.

These constraints support the same options as the other constraints, except the `value`, `propertyPath`, `currency`, `currencyMismatchMessage`, and `scalarUnit` options. As the sign of an amount does not depend on its unit, integer, float, and integer string values do not need the `scalarUnit` option.

```php
#[MoneyAssert\MoneyPositive]
public Money $price;
```

### `MoneyRange`

Validates that a value is between a minimum and/or maximum value, inclusive. The limits are set with the `min` and `max` options, or read from the validated object with the `minPropertyPath` and `maxPropertyPath` options; at least one limit is required, and a limit read from a property path which is not initialized is ignored.

This constraint uses its own messages instead of the `message` option:

- `notInRangeMessage` - The message used when both a minimum and maximum are set; supports the `{{ value }}`, `{{ min }}`, and `{{ max }}` parameters
- `minMessage` and `maxMessage` - The messages used when only a minimum or maximum is set; support the `{{ value }}` and `{{ limit }}` parameters

The `{{ min_limit_path }}` and `{{ max_limit_path }}` parameters are also available when the limits are read from property paths. The constraint supports the same conversion and display options as the other constraints, and a limit in a different currency than the value adds a violation using the `currencyMismatchMessage` option.

```php
#[MoneyAssert\MoneyRange(min: '1.00', max: '100.00')]
public Money $price;
```

## Constraint Options

The constraints support the following extra options, similar to the comparison constraints provided by the Validator component:

- `currencyMismatchMessage` - This is the message that will be shown if the value and the compared value have different currencies, and supports the same parameters as the `message` option
- `excessFractionDigitsMessage` - This is the message that will be shown if the `rejectExcessFractionDigits` option is enabled and the value being validated has more fraction digits than its currency; supports the `{{ value }}` and `{{ limit }}` parameters
- `groups` - Defines the validation group(s) this constraint belongs to
- `invalidMessage` - This is the message that will be shown if the value being validated cannot be converted into a `Money\Money` instance; supports the `{{ value }}` parameter
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
- `parserFormat` - The format used to parse a formatted string into a `Money\Money` instance, as one of the `BabDev\MoneyBundle\Format` constants; defaults to `Format::DECIMAL`
- `rejectExcessFractionDigits` - Whether a value being validated with more fraction digits than its currency, such as `'18.123'` in US dollars, adds a violation using the `excessFractionDigitsMessage` option, with the `AbstractMoneyComparison::TOO_MANY_FRACTION_DIGITS_ERROR` code, instead of being rounded; defaults to false
- `scalarUnit` - The unit of integer, float, and integer string values, either `AbstractMoneyComparison::UNIT_MINOR` (`minor`) or `AbstractMoneyComparison::UNIT_MAJOR` (`major`); see [Scalar Values](#scalar-values)
- `fractionDigits` - The number of fraction digits used by the intl formatters; defaults to the number of decimal places used by the value's currency
- `groupingUsed` - Whether the intl formatters and parsers use grouping separators; defaults to true
- `locale` - The locale used by the intl formatters and parsers; the formatters default to the current request's locale (or the `kernel.default_locale` parameter outside of a request), and the parsers default to the `kernel.default_locale` parameter so values defined in code are always parsed the same way
- `style` - The number style used by the intl formatters and parsers, either `currency` or `decimal`; defaults to `decimal` for the `intl_localized_decimal` format and `currency` for the `intl_money` format

```php
#[MoneyAssert\MoneyGreaterThanOrEqual(value: '10.00', currency: 'EUR', locale: 'de')]
public Money $price;
```

## Scalar Values

Scalar values, for both the `value` option and the value being validated, are converted into a `Money\Money` instance for comparison:

- A formatted string, meaning any string other than an integer string (such as `'10.00'` or `'10,00 €'`), is parsed using the `parserFormat` option and represents an amount in the currency's major unit
- An integer, float, or integer string (such as `1000` or `'1000'`) represents an amount in the unit set by the `scalarUnit` option:
    - `minor` - The amount is in the currency's minor unit, so `1000` is $10.00 in US dollars, the same as `new Money(1000, new Currency('USD'))`; floats must be whole numbers
    - `major` - The amount is in the currency's major unit, so `1000` is $1,000.00 and `10.5` is $10.50 in US dollars

```php
#[MoneyAssert\MoneyLessThanOrEqual(value: 1000, scalarUnit: AbstractMoneyComparison::UNIT_MAJOR)]
public Money $price;
```

<div class="docs-note">Not setting the <code>scalarUnit</code> option when comparing an integer, float, or integer string is deprecated; these values are treated as minor units and trigger a deprecation, and the default will change to major units in 4.0. Set the option to <code>minor</code> to keep the current behavior or <code>major</code> to opt in to the new behavior.</div>

A value being validated which cannot be converted, such as `'test'` or a fractional float in minor units, adds a violation using the `invalidMessage` option, with the `AbstractMoneyComparison::INVALID_VALUE_ERROR` code (`MoneyRange::INVALID_VALUE_ERROR` for the `MoneyRange` constraint).

Note that all option values from XML mappings are strings, so `<option name="value">1000</option>` is an integer string and uses the `scalarUnit` option.

## Currencies

When a scalar value is converted into a `Money\Money` instance for comparison, it uses the constraint's `currency` option if set. Otherwise, it uses the currency of the other value when that value is a `Money\Money` instance, so a constraint such as `#[MoneyGreaterThan(value: 0)]` works with values in any currency. If neither value is a `Money\Money` instance, the `babdev_money.default_currency` configuration value is used.

Values with different currencies cannot be ordered, so the `MoneyGreaterThan`, `MoneyGreaterThanOrEqual`, `MoneyLessThan`, and `MoneyLessThanOrEqual` constraints add a violation using the `currencyMismatchMessage` option, with the `AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR` code, instead of comparing them. For the `MoneyEqualTo` and `MoneyNotEqualTo` constraints, values with different currencies are not equal.

## Translations

The default messages for the constraints are translated in the `validators` domain. The `message` defaults reuse the wording of the comparison constraints from the Validator component, and the `invalidMessage` default reuses the wording of the `Range` constraint's `invalidMessage`, so they are translated by the Validator component's own translations. The bundle provides English translations for the `currencyMismatchMessage` and `excessFractionDigitsMessage` defaults; to translate it into other languages, add the message to your application's `validators` translation files:

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
