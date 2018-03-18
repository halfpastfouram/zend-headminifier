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