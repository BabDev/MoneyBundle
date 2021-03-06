# Twig

The MoneyBundle provides support for creating rendering `Money` instances within [Twig](https://twig.symfony.com/) (note, the `TwigBundle` must be installed to automatically enable the Twig extension).

## Filters

### `money` Filter

When rendering a Twig template, you can use the `money` filter with a `Money` instance to format the object into a string for display. [Most of the formatters](https://moneyphp.org/en/stable/features/formatting.html) from the Money library are available using the `$format` parameter of the filter (the aggregate formatter is not supported).

The filter has the following additional optional arguments:

- $format - An identifier for the formatter to use, all formatters use a snake case version of the formatter class without the "MoneyFormatter" suffix (i.e. to use the Bitcoin formatter the key name is "bitcoin"); this defaults to "intl_money"
- $locale - The locale to use while formatting the `Money` instance, only supported with the intl formatters, defaults to the value of the `kernel.default_locale` parameter if one is not provided
- $options - Additional options to configure the formatters, currently supported keys are "fraction_digits", "grouping_used", and "style" (note not all formatters support all options)

Below is a basic example of rendering the `$tax_due` property from an `Invoice` entity (which is typed as a `Money` instance), the default configuration will be used:

```twig
{{ invoice.tax_due|money }}
```

Below is an advanced example of rendering the `$tax_due` property, passing additional options through the filter

```twig
{{ invoice.tax_due|money('decimal', 'de', {fraction_digits: 0}) }}
```

## Functions

### `money` Function

When rendering a Twig template, you can use the `money` function to create a `Money` instance to use in a template.

You must provide an amount compatible with the `Money` class' constructor (i.e. an amount expressed in cents). The function also has an optional currency argument, and if omitted will use the default currency set in your application's configuration.

```twig
{# Creates a new Money instance in the default currency #}
{% set total = money(0) %}

{# Creates a new Money instance in the specified currency #}
{% set total = money(0, 'EUR') %}
```
