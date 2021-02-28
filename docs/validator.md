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

- `groups` - Defines the validation group(s) this constraint belongs to
- `message` - This is the message that will be shown if the value fails the validation check; messages have the following parameters available:
    - `{{ compared_value }}` - The value being compared to
    - `{{ compared_value_type }}` - The expected value type
    - `{{ value }}` - The current (invalid) value
- `payload` - This option can be used to attach arbitrary domain-specific data to a constraint, it is not used by the Validator component, but its processing is completely up to you
- `propertyPath` - Defines the object property whose value is used to make the comparison
- `value` - This option is required; it defines the value to compare to, this should be a `Money\Money` instance or a scalar value (string/int/float) that can be parsed into a `Money\Money` instance

## Form Support

When used alongside the [Symfony Form component](https://symfony.com/doc/current/components/form.html), the constraints can be used with your forms to validate your data.

## Examples

### Annotations

<div class="docs-note">Annotations support requires the <a href="https://www.doctrine-project.org/projects/annotations.html">Doctrine Annotations</a> library.</div>

```php
<?php

namespace App\Entity;

use BabDev\MoneyBundle\Validator\Constraints as MoneyAssert;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

/**
 * @ORM\Table()
 */
class Invoice
{
    /**
     * @ORM\Embedded(class="Money\Money")
     * @MoneyAssert\MoneyGreaterThanOrEqual(value = 0)
     */
    public Money $tax_due;

    public function __construct()
    {
        $this->tax_due = Money::USD(0);
    }
}
```

### Attributes

<div class="docs-note">Attribute support requires PHP 8.</div>

```php
<?php

namespace App\Entity;

use BabDev\MoneyBundle\Validator\Constraints as MoneyAssert;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

/**
 * @ORM\Table()
 */
class Invoice
{
    /**
     * @ORM\Embedded(class="Money\Money")
     */
    #[MoneyAssert\MoneyGreaterThanOrEqual(
        value: 0,
    )]
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

namespace App\Entity;

use BabDev\MoneyBundle\Validator\Constraints as MoneyAssert;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Table()
 */
class Invoice
{
    /**
     * @ORM\Embedded(class="Money\Money")
     */
    public Money $tax_due;

    public function __construct()
    {
        $this->tax_due = Money::USD(0);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'tax_due',
            new MoneyAssert\MoneyGreaterThanOrEqual([
                'value' => 0,
            ])
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
