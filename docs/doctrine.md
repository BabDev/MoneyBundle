# Doctrine

When used with an application where the [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) (and DoctrineBundle) is installed, the MoneyBundle defines an appropriate database schema allowing `Money\Money` objects to be used within entities with no extra effort.

All that is required is to define a field on your entity as an embedded field with the `Money\Money` type, such as the below example entity:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Money;

/**
 * @ORM\Entity()
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
}
```

That's it. When you update your database schema, the schema for the `Money\Money` object will be automatically generated for you.
