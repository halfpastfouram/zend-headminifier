[![Latest Stable Version](https://poser.pugx.org/halfpastfouram/zend-headminifier/version)](https://packagist.org/packages/halfpastfouram/zend-headminifier)
[![Maintainability](https://api.codeclimate.com/v1/badges/a5a280e09dd75c47c89b/maintainability)](https://codeclimate.com/github/halfpastfouram/zend-headminifier/maintainability)
[![composer.lock available](https://poser.pugx.org/halfpastfouram/zend-headminifier/composerlock)](https://packagist.org/packages/halfpastfouram/zend-headminifier)
[![License](https://poser.pugx.org/halfpastfouram/zend-headminifier/license)](https://packagist.org/packages/halfpastfouram/zend-headminifier)
[![Total Downloads](https://poser.pugx.org/halfpastfouram/zend-headminifier/downloads)](https://packagist.org/packages/halfpastfouram/zend-headminifier)

# ZF3 Head Minifier
ZF3 module that minifies head links and head scripts using [matthiasmullie/minify](https://github.com/matthiasmullie/minify).

## Installation:

Require this module via composer:

```
composer require halfpastfouram/zend-headminifier
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
                'js'     => './public/js',
                'cache'  => './public/js',
            ],
        ],
        'link'   => [
            'enabled'     => true,
            'directories' => [
                'public' => './public',
                'css'     => './public/css',
                'cache'  => './public/css',
            ],
        ],
    ],
```
