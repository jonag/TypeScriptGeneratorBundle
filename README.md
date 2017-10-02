# TypeScriptGeneratorBundle
This bundle adds two Symfony command to generate TypeScript classesor interfaces from PHP classes.

The fields and their types are extracted from the phpdoc, Doctrine's metadata and PHP's types using [Symfony's Property Info Component](https://symfony.com/doc/current/components/property_info.html) .

## Installation
Require the bundle in Composer:
```
composer require --dev a5sys/typescript-generator-bundle
```
Register the bundle in your AppKernel:
```php
public function registerBundles()
{
    // ...
    if ('dev' === $this->getEnvironment()) {
        // ...
        $bundles[] = new A5sys\TypeScriptGeneratorBundle\A5sysTypeScriptGeneratorBundle();
    }
}
```
## Usage
To generate classes:
```
mkdir generated-classes
php bin/console a5sys:ts-generator:class src/AppBundle/Entity generated-classes
```
This command will scan all classes in the directory `src/AppBundle/Entity` and generate their typescript's equivalence in the directory `generated-classes`.

To generate interfaces:
```
mkdir generated-interfaces
php bin/console a5sys:ts-generator:interface src/AppBundle/Entity generated-interfaces
```
This command will scan all classes in the directory `src/AppBundle/Entity` and generate their typescript's equivalence in the directory `generated-interface`.
