[![Latest Stable Version](https://poser.pugx.org/halfpastfouram/zend-headminifier/version)](https://packagist.org/packages/halfpastfouram/zend-headminifier)
[![composer.lock available](https://poser.pugx.org/halfpastfouram/zend-headminifier/composerlock)](https://packagist.org/packages/halfpastfouram/zend-headminifier)
[![License](https://poser.pugx.org/halfpastfouram/zend-headminifier/license)](https://packagist.org/packages/halfpastfouram/zend-headminifier)
[![Total Downloads](https://poser.pugx.org/halfpastfouram/zend-headminifier/downloads)](https://packagist.org/packages/halfpastfouram/zend-headminifier)
[![Maintainability](https://api.codeclimate.com/v1/badges/a5a280e09dd75c47c89b/maintainability)](https://codeclimate.com/github/halfpastfouram/zend-headminifier/maintainability)
[![Scrutinizer score](https://scrutinizer-ci.com/g/halfpastfouram/zend-headminifier/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/halfpastfouram/zend-headminifier/)
[![Scrutinizer build status](https://scrutinizer-ci.com/g/halfpastfouram/zend-headminifier/badges/build.png?b=master)](https://scrutinizer-ci.com/g/halfpastfouram/zend-headminifier/build-status/master)

# ZF3 Head Minifier
ZF3 module that minifies head links and head scripts using [matthiasmullie/minify](https://github.com/matthiasmullie/minify).

## Installation:

Require this module via composer:

```
$ composer require halfpastfouram/zend-headminifier
```

Add the module to `modules.config.php`:

```php
'Halfpastfour\HeadMinifier'
```

## Configuration

The following settings can be changed in your global configuration file:

```php
    'minify_head' => [
        'script' => [
            'enabled'     => true,
            'directories' => [
                'public' => './public',
                'cache'  => './public/js',
            ],
        ],
        'link'   => [
            'enabled'     => true,
            'directories' => [
                'public' => './public',
                'cache'  => './public/css',
            ],
        ],
    ],
```
