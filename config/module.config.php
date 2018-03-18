<?php

namespace Halfpastfour\HeadMinifier;

use Halfpastfour\HeadMinifier\Factory\View\Helper\HeadLinkFactory;
use Halfpastfour\HeadMinifier\Factory\View\Helper\HeadScriptFactory;
use Halfpastfour\HeadMinifier\View\Helper\HeadLink;
use Halfpastfour\HeadMinifier\View\Helper\HeadScript;

return [
    'view_helpers' => [
        'factories' => [
            HeadLink::class   => HeadLinkFactory::class,
            HeadScript::class => HeadScriptFactory::class,
        ],

        'aliases' => [
            'headLink'   => HeadLink::class,
            'headScript' => HeadScript::class,
        ],
    ],

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
];