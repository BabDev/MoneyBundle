# MoneyBundle

The MoneyBundle is a [Symfony](https://symfony.com/) bundle integrating the [Money for PHP](https://github.com/moneyphp/money) library into an application.

This bundle is inspired by the [JKMoneyBundle](https://github.com/kucharovic/money-bundle) with miscellaneous enhancements based on its use with client projects.

The bundle includes:

- Schema allowing `Money` objects to be used with the [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/mongodb-odm.html) and [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- Serializing and deserializing `Money` objects using the [Symfony Serializer component](https://symfony.com/doc/current/components/serializer.html) or the [JMS Serializer](https://jmsyst.com/libs/serializer)
- A [Twig](https://twig.symfony.com/) integration to create and render formatted `Money` objects
- A form type for the [Symfony Form component](https://symfony.com/doc/current/components/form.html) to process `Money` objects
- Comparing `Money` object values using the [Symfony Validator component](https://symfony.com/doc/current/components/validator.html)
