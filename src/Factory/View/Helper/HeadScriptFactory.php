<?php

namespace Halfpastfour\HeadMinifier\Factory\View\Helper;

use Halfpastfour\HeadMinifier\View\Helper\HeadScript;
use Interop\Container\ContainerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class HeadScriptFactory
 *
 * @package Halfpastfour\HeadMinifier\Factory\View\Helper
 */
class HeadScriptFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     *
     * @return object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $applicationConfig = (array)$container->get('config');
        if (array_key_exists('minify_head', $applicationConfig)
            && array_key_exists('script', $applicationConfig['minify_head'])) {
            $config = $applicationConfig['minify_head']['script'];
        } else {
            throw new \Exception('Configuration not available.');
        }

        /** @var Request $request */
        $request = $container->get('Request');
        $baseUrl = '';

        if ($request instanceof Request) {
            $baseUrl = $request->getBasePath();
        }

        return new HeadScript($config, $baseUrl);
    }
}
