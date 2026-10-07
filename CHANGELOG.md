# Changelog

## 3.2.0 (????-??-??)

- Restore the ability to configure the `currency`, `formatterFormat`, `parserFormat`, `fractionDigits`, `groupingUsed`, `locale`, and `style` options for the money comparison validation constraints, which were not accepted as named arguments after the options array was removed in 3.0
- Only register the Doctrine ORM and MongoDB ODM mappings when the respective bundle is registered and its ORM/ODM layer is configured, fixing a container compile error when the packages are installed but not enabled
- Make the `property_accessor` service an optional dependency of the money comparison validators, fixing a container compile error when the Symfony PropertyAccess component is not available
- The money comparison validation constraints no longer throw an exception when comparing values with different currencies
- Scalar values now use the currency of the `Money\Money` instance they are compared to when the constraint's `currency` option is not set
- Add a violation with the new `currencyMismatchMessage` option and `AbstractMoneyComparison::CURRENCY_MISMATCH_ERROR` code when currencies differ
- Add an English translation for the currency mismatch message in the `validators` translation domain
- Add a `scalarUnit` option to the money comparison validation constraints to set whether integer, float, and integer string values are amounts in minor or major units
- Formatted string values for the money comparison validation constraints are now always parsed with the `parserFormat` option
- Deprecated not setting the `scalarUnit` option when comparing a non-zero integer, float, or integer string value with the money comparison validation constraints; these values are currently treated as minor units, and the default will change to major units in 4.0
- The `scale` option for the `MoneyType` form type now defaults to the number of decimal places used by the currency
- The `MoneyType` form type now throws an `InvalidOptionsException` if the `scale` option is greater than the number of decimal places used by the currency, or if the currency is not supported
- The `intl_money` and `intl_localized_decimal` formatters now use the number of decimal places used by the currency when the `fraction_digits` option is not set
- Invalid data passed to the serializer integrations now always raise contextually appropriate exceptions
- The formatter factory now uses the current request's locale when no locale is given; the parser factory still uses the `kernel.default_locale` parameter
- Deprecated the `input` option for the `MoneyType` form type
- Add a `money.currencies` service, which can be redefined to support currencies other than the ISO 4217 currencies
- The formatter factory now reuses the formatters it creates for the same format, locale, and options
- The `default_currency` configuration option must now be a non-empty string
- Add `MoneyRange`, `MoneyPositive`, `MoneyPositiveOrZero`, `MoneyNegative`, and `MoneyNegativeOrZero` validation constraints
- The `intl_localized_decimal` format now uses the decimal style by default
- Fix serializing a `Money\Money` instance nested in another value to XML with the JMS Serializer
- The money validation constraints now report values which cannot be converted to a `Money\Money` instance as a type violation instead of raising a `TypeError`

## 3.1.0 (2026-05-12)

- Address deprecated `$aliasMap` argument from `Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass::createXmlMappingDriver()`

## 3.0.0 (2025-12-30)

- Consult the UPGRADE guide for changes between 2.x and 3.0
