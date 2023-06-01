# Changelog

## 1.8.0 (2023-05-31)

- Add support for `Symfony\Component\Serializer\Normalizer\NormalizerInterface::getSupportedTypes()` for Symfony 6.3+

## 1.7.0 (2022-05-27)

- Drop support for Symfony 5.3
- Add support for Symfony 6.1

## 1.6.1 (2021-11-30)

- Allow v3 of `symfony/deprecation-contracts`

## 1.6.0 (2021-11-23)

- Moved the Doctrine mapping files for Symfony 5.4 compatibility

## 1.5.0 (2021-11-09)

- Add support for Symfony 6
- *Minor B/C Break* `BabDev\MoneyBundle\Form\DataTransformer\MoneyToLocalizedStringTransformer` no longer extends from `Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer`
- Deprecated passing `Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer` constructor arguments into `BabDev\MoneyBundle\Form\DataTransformer\MoneyToLocalizedStringTransformer`, as of 2.0 the decorated transformer will be required instead 

## 1.4.0 (2021-08-01)

- Fix field name for Doctrine MongoDB ODM embeddable
- Drop support for Symfony 5.2 (Branch is EOL)

## 1.3.1 (2021-06-11)

- Fix mapping paths for Doctrine integrations

## 1.3.0 (2021-06-10)

- Add an enum class with the supported format codes
- Add support for Doctrine MongoDB ODM
- Register ORM mappings through a compiler pass

## 1.2.0 (2021-05-17)

- Add support for `moneyphp/money` 4.0

## 1.1.0 (2021-04-25)

- Use localized exceptions for the factories

## 1.0.0 (2021-02-28)

- Functionally similar to 0.1 release, contains minor static analysis and documentation improvements only
