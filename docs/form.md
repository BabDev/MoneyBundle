# Form

The MoneyBundle provides support for forms using a `Money` instance as a data object by providing an alternative `MoneyType` form type for the [Symfony Form component](https://symfony.com/doc/current/components/form.html).

With the Form component installed, the form type will automatically be registered to your application and available for use in your form classes.

Note, this form type uses the same block prefix as the native `MoneyType` provided by the Form component; this allows the form type provided by this bundle to act as a drop-in replacement from a templating perspective.

```php
<?php

namespace App\Form;

use BabDev\MoneyBundle\Form\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'app.form.label.product_name',
                ]
            )
            ->add(
                'price',
                MoneyType::class,
                [
                    'label' => 'app.form.label.price',
                ]
            )
        ;
    }
}
```

You can also use the <a href="/open-source/packages/moneybundle/docs/1.x/validator">validator integration</a> to define constraints on your form types.

```php
<?php

namespace App\Form;

use BabDev\MoneyBundle\Form\Type\MoneyType;
use BabDev\MoneyBundle\Validator\Constraints\MoneyGreaterThanOrEqual;use Money\Currency;use Money\Money;use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'app.form.label.product_name',
                ]
            )
            ->add(
                'price',
                MoneyType::class,
                [
                    'label' => 'app.form.label.price',
                    'currency' => new Currency('EUR'),
                    'constraints' => [
                        new MoneyGreaterThanOrEqual(Money::EUR(0)),
                    ]
                ]
            )
        ;
    }
}
```

## Options

The `MoneyType` form type largely accepts the same options as the form type from the Form component:

- `currency` - Specifies the currency that the money is being specified in. Unlike the Form component, this must be provided as a `Money\Currency` instance instead of a string. If not specified, the default currency from the `babdev_money.default_currency` configuration node is used.
- `grouping` - This value is used internally as the `NumberFormatter::GROUPING_USED` value when using PHP's `NumberFormatter` class. This should be a boolean value and defaults to false. Note, this option cannot be used with the `html5` option.
- `rounding_mode` - If a submitted number needs to be rounded (based on the `scale` option), you have several configurable options for that rounding. This should be one of the `NumberFormatter::ROUNDING_*` constants and defaults to `NumberFormatter::ROUND_HALFUP`.
- `html5` - If set to true, the HTML input will be rendered as a native HTML5 number input. This should be a boolean value and defaults to false.
- `scale` - If, for some reason, you need some scale other than 2 decimal places, you can modify this value. This should be an integer and defaults to 2.
