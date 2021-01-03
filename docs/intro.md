# MoneyBundle

The MoneyBundle is a [Symfony](https://symfony.com/) bundle integrating the [Money for PHP](https://github.com/moneyphp/money) library into an application.

This bundle is inspired by the [JKMoneyBundle](https://github.com/kucharovic/money-bundle) with miscellaneous enhancements based on its use with client projects.

The bundle includes:

- Database schema allowing `Money` objects to be used with the [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- Serializing and deserializing `Money` objects using the [Symfony Serializer component](https://symfony.com/doc/current/components/serializer.html) or the [JMS Serializer](https://jmsyst.com/libs/serializer)
- A [Twig](https://twig.symfony.com/) filter to render formatted `Money` objects
