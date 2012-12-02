[![Build Status](https://secure.travis-ci.org/Ocramius/OcraServiceManager.png?branch=master)](https://travis-ci.org/Ocramius/OcraServiceManager)

# OcraServiceManager

OcraServiceManager is a Zend Framework 2 Module that decorates `Zend\ServiceManager\ServiceManager`
with the ability to generate lazily initialized service proxies.

It is **heavily tested** and supports proxying of any possible object type.

If you don't know what proxies are, you can read my article about
[service proxies and why we need them](http://ocramius.github.com/blog/zf2-and-symfony-service-proxies-with-doctrine-proxies/).

## Installation

The recommended way to install
[`ocramius/ocra-service-manager`](https://packagist.org/packages/ocramius/ocra-service-manager) is through
[composer](http://getcomposer.org/):

```sh
php composer.phar require ocramius/ocra-service-manager
```

When asked for a version to install, type `*`.
You can then enable it in your `config/application.config.php` by adding
`OcraServiceManager` to your modules.

## Configuration

Following config keys are provided by default, but you can change them as you want:

```php
return array(
    'service_manager' => array(
        'lazy_services' => array(
            // set the names of your lazily loaded services here
        ),
    ),
    'ocra_service_manager' => array(
        // Namespace of generated proxies
        'service_proxies_namespace' => ServiceProxyGenerator::DEFAULT_SERVICE_PROXY_NS,

        // directory where to store proxies, by default `data/service-proxies` in your app
        'service_proxies_dir'       => getcwd() . '/data/service-proxies',

        // name of the cache service to be used to cache service proxies definitions
        'service_proxies_cache'     => 'OcraServiceManager\\Cache\\ServiceProxyCache',

        // config used to instantiate 'OcraServiceManager\\Cache\\ServiceProxyCache'
        'cache'                     => array(
            // configuration to be passed to `Zend\Cache\StorageFactory#factory()` here
        ),
    ),
);
```

## Tweaking for production

Please be aware that the default settings will not make your application faster until
you set effective cache settings in `config.ocra_service_manager.cache`.

## Testing

After having installed via composer:

```sh
cd path/to/ocra-service-manager
phpunit
```

## Known limitations:

 * Currently only replaces only the main `service_manager` of your application.
 * Service proxies are useless if you initialize them via service initializers, since any call
   to a method of the proxy itself will initialize it.

