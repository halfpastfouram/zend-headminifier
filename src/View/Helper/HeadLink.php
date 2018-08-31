<?php

namespace Halfpastfour\HeadMinifier\View\Helper;

use MatthiasMullie\Minify;

/**
 * Class HeadLink
 *
 * @package Halfpastfour\HeadMinifier\View\Helper
 */
class HeadLink extends \Zend\View\Helper\HeadLink
{
    /**
     * @var array
     */
    private $options = [];

    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * HeadLink constructor.
     *
     * @param array  $config
     * @param string $baseUrl
     */
    public function __construct(array $config, string $baseUrl)
    {
        $this->options = $config;
        $this->baseUrl = $baseUrl;

        parent::__construct();
    }

    /**
     * @param null $indent
     *
     * @return string
     */
    public function toString($indent = null): string
    {
        // If configuration tells us minifying is not enabled, use the default view helper.
        if (! $this->options['enabled']) {
            return parent::toString($indent);
        }

        $cacheItems = [];
        $publicDir  = $this->options['directories']['public'];
        $cacheDir   = $this->options['directories']['cache'];
        $cssDir     = str_replace($publicDir, '', $cacheDir);
        $items      = $this->processItems($publicDir, $cacheItems);

        /** @noinspection PhpUndefinedMethodInspection */
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $identifier   = sha1(implode($cacheItems));
        $minifiedFile = "/{$identifier}.min.css";
        $links        = $this->minifyFile($minifiedFile, $cacheDir, $cacheItems)
                             ->generateLinks($items, $cssDir, $minifiedFile);

        /** @noinspection PhpUndefinedMethodInspection */
        return $indent . implode($this->escape($this->getSeparator()) . $indent, $links);
    }

    /**
     * @param string $publicDir
     * @param array  $cacheItems
     *
     * @return array
     */
    private function processItems($publicDir, array &$cacheItems)
    {
        $items = [];
        foreach ($this as $item) {
            if (! $item->href || $item->type != 'text/css') {
                continue;
            }
            $localUri  = str_replace($this->baseUrl, '', preg_replace('/\?.*/', '', $publicDir . $item->href));
            $remoteUri = $item->href;
            $handle    = curl_init($remoteUri);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            if (is_file($localUri)) {
                $cacheItems[] = $localUri;
            } elseif (($output = curl_exec($handle)) !== false) {
                $cacheItems[] = $remoteUri;
            } else {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param string $minifiedFile
     * @param string $cacheDir
     * @param array  $cacheItems
     *
     * @return $this
     */
    private function minifyFile($minifiedFile, $cacheDir, array $cacheItems)
    {
        if (! is_file($this->baseUrl . $minifiedFile)) {
            $minifier = new Minify\CSS();
            array_map(function ($uri) use ($minifier) {
                $minifier->add($uri);
            }, $cacheItems);
            $minifier->minify($cacheDir . $minifiedFile);
        }

        return $this;
    }

    /**
     * @param array  $items
     * @param string $cssDir
     * @param string $minifiedFile
     *
     * @return array
     */
    private function generateLinks(array $items, $cssDir, $minifiedFile)
    {
        $links = [
            $this->itemToString($this->createData([
                'type'                  => 'text/css',
                'rel'                   => 'stylesheet',
                'href'                  => $this->baseUrl . $cssDir . $minifiedFile,
                'conditionalStylesheet' => false,
            ])),
        ];

        foreach ($items as $item) {
            $links[] = $this->itemToString($item);
        }

        return $links;
    }
}
