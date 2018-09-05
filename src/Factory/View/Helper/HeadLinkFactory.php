<?php

namespace Halfpastfour\HeadMinifier\Factory\View\Helper;

use Halfpastfour\HeadMinifier\View\Helper\HeadLink;
use Interop\Container\ContainerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class HeadLinkFactory
 *
 * @package Halfpastfour\HeadMinifier\Factory\View\Helper
 */
class HeadLinkFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     *
     * @return object
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $applicationConfig = (array)$container->get('config');
        if (! array_key_exists('minify_head', $applicationConfig)
            && ! array_key_exists('script', $applicationConfig['minify_head'])) {
            throw new \Exception('Configuration not available.');
        }
        $config  = $applicationConfig['minify_head']['link'];
        $request = $container->get('Request');

        return new HeadLink(
            $config,
            $request instanceof Request ? $request->getBasePath() : ''
        );
    }
}
