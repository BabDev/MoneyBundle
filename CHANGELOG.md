# Changelog

## 3.1.1 (????-??-??)

- Restore the ability to configure the `currency`, `formatterFormat`, `parserFormat`, `fractionDigits`, `groupingUsed`, `locale`, and `style` options for the money comparison validation constraints, which were not accepted as named arguments after the options array was removed in 3.0
- Only register the Doctrine ORM and MongoDB ODM mappings when the respective bundle is registered and its ORM/ODM layer is configured, fixing a container compile error when the packages are installed but not enabled

## 3.1.0 (2026-05-12)

- Address deprecated `$aliasMap` argument from `Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass::createXmlMappingDriver()`

## 3.0.0 (2025-12-30)

- Consult the UPGRADE guide for changes between 2.x and 3.0
