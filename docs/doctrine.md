# Doctrine

## MongoDB ODM

When used with an application where the [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/mongodb-odm.html) (and DoctrineMongoDBBundle) is installed, the MoneyBundle defines an appropriate schema allowing `Money\Money` objects to be embedded within documents with no extra effort.

All that is required is to define a field on your document as an embedded field with the `Money\Money` type, such as the below example document:

<div class="docs-note docs-note--tip">The below example uses PHP 8 Attributes, but you can use any of the ODM's mapping drivers in your application.</div>

```php
<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Money\Money;

#[ODM\Document]
class Invoice
{
    #[ODM\EmbedOne(targetDocument: Money::class)]
    public Money $tax_due;

    public function __construct()
    {
        $this->tax_due = Money::USD(0);
    }
}
```

## ORM

When used with an application where the [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) (and DoctrineBundle) is installed, the MoneyBundle defines an appropriate database schema allowing `Money\Money` objects to be used within entities with no extra effort.

All that is required is to define a field on your entity as an embedded field with the `Money\Money` type, such as the below example entity:

<div class="docs-note docs-note--tip">The below example uses PHP 8 Attributes, but you can use any of the ORM's mapping drivers in your application.</div>

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Money;

#[ORM\Entity]
class Invoice
{
    #[ORM\Embedded(class: Money::class)]
    public Money $tax_due;

    public function __construct()
    {
        $this->tax_due = Money::USD(0);
    }
}
```
