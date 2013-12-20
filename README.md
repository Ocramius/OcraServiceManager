# OcraServiceManager

OcraServiceManager is a Zend Framework 2 Module that decorates `Zend\ServiceManager\ServiceManager`
to allow tracking dependencies between services.

It integrates with [ZendDeveloperTools](https://github.com/zendframework/ZendDeveloperTools)
to provide aid in debugging what is happening between your dependencies, allowing you to produce
dependency graphs such as the following one:

![Example Dependency Graph generated by `OcraServiceManager`](http://yuml.me/1c92f6a5.png)

It is **heavily tested** and supports proxying of any possible object type.

If you don't know what proxies are, you can read my article about
[service proxies and why we need them](http://ocramius.github.com/blog/zf2-and-symfony-service-proxies-with-doctrine-proxies/).

## Status

| Tests | Releases | Downloads | Dependencies |
| ----- | -------- | ------- | ------------- | --------- | ------------ |
|[![Build Status](https://travis-ci.org/Ocramius/OcraServiceManager.png?branch=master)](https://travis-ci.org/Ocramius/OcraServiceManager) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Ocramius/OcraServiceManager/badges/quality-score.png?s=60bf25ba177630bffe7bce2f4bb3eea93f1a0f55)](https://scrutinizer-ci.com/g/Ocramius/OcraServiceManager/) [![Coverage Status](https://coveralls.io/repos/Ocramius/OcraServiceManager/badge.png?branch=master)](https://coveralls.io/r/Ocramius/OcraServiceManager)|[![Latest Stable Version](https://poser.pugx.org/ocramius/ocra-service-manager/v/stable.png)](https://packagist.org/packages/ocramius/ocra-service-manager) [![Latest Unstable Version](https://poser.pugx.org/ocramius/ocra-service-manager/v/unstable.png)](https://packagist.org/packages/ocramius/ocra-service-manager)|[![Total Downloads](https://poser.pugx.org/ocramius/ocra-service-manager/downloads.png)](https://packagist.org/packages/ocramius/ocra-service-manager)|[![Dependency Status](https://www.versioneye.com/package/php--ocramius--ocra-service-manager/badge.png)](https://www.versioneye.com/package/php--ocramius--ocra-service-manager)|

## Installation

The recommended way to install
[`ocramius/ocra-service-manager`](https://packagist.org/packages/ocramius/ocra-service-manager) is through
[composer](http://getcomposer.org/):

```sh
php composer.phar require ocramius/ocra-service-manager
```

You can then enable this module in your `config/application.config.php` by adding
`OcraServiceManager` to the `modules` key:

```php
// ...
    'modules' => array(
        // add OcraServiceManager and ZDT...
        'ZendDeveloperTools',
        'OcraServiceManager',
        // ... and then your stuff
        'MyOwnModule',
    ),
```

Please note that you need to enable service manager logging and the ZendDeveloperTools toolbar
to actually see something working.

## Configuration

Following config keys are provided by default, but you can change them as you want. You can
drop a file `ocra-service-manager.local.php` into your `config/autoload` directory to
enable or disable logging of your service manager instances:

```php
return array(
    'ocra_service_manager' => array(
        // Turn this on to disable dependencies view in Zend Developer Tools
        'logged_service_manager' => true,
    ),
);
```

Please note that logging is enabled by default

## Testing and Contributing

Please refer to the contents of [`.travis.yml`](.travis.yml) to see how to test your patches
against OcraServiceManager.

Any pull requests will be accepted only if:

 * code coverage on newly introduced code is >= 90% (use `@coversNothing` on integration tests, please)
 * coding standard complies
   with [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
 * generally tries to respect
   [object calisthenics](http://www.slideshare.net/guilhermeblanco/object-calisthenics-applied-to-php)

## Known limitations:

 * Installing the module itself won't allow tracking the first service-manager events in
   your application. If you need to have that working, you will need to override the
   implementation of `Zend\Mvc\Application::init()`
