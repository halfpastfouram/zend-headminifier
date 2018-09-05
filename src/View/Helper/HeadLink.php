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

        // Process all items. The items that don't require any changes will be returned in $items. The items that will
        // be cached will be returned in $cacheItems.
        $items = $this->processItems($publicDir, $cacheItems);

        /** @noinspection PhpUndefinedMethodInspection */
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $identifier   = sha1(implode($cacheItems));
        $minifiedFile = "/{$identifier}.min.css";

        // Create a minified file containing all cache items. Return the name of the minified file as the last item in
        // returned in $items.
        $links = $this->minifyFile($minifiedFile, $cacheDir, $cacheItems, $items)
            // Generate the links
                      ->generateLinks($items);

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
        foreach ($this as $index => $item) {
            if (! $item->href || $item->type != 'text/css') {
                continue;
            }
            $localUri  = str_replace($this->baseUrl, '', preg_replace('/\?.*/', '', $publicDir . $item->href));
            $remoteUri = $item->href;
            $handle    = curl_init($remoteUri);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

            if (is_file($localUri)) {
                $cacheItems[$index] = $localUri;
                continue;
            }

            if (curl_exec($handle) !== false) {
                $cacheItems[$index] = $remoteUri;
                continue;
            }

            $items[$index] = $item;
        }

        return $items;
    }

    /**
     * @param string $minifiedFile
     * @param string $cacheDir
     * @param array  $cacheItems
     * @param array  $items
     *
     * @return $this
     */
    private function minifyFile($minifiedFile, $cacheDir, array $cacheItems, array &$items)
    {
        if (! is_file($cacheDir . $minifiedFile) && ! empty($cacheItems)) {
            $minifier = new Minify\CSS();
            array_map(function ($uri) use ($minifier) {
                $minifier->add($uri);
            }, $cacheItems);
            $minifier->minify($cacheDir . $minifiedFile);

            // Add the minified file tot the list of items.
            $items[] = $this->createData([
                'type'                  => 'text/css',
                'rel'                   => 'stylesheet',
                'href'                  => $cacheDir . $minifiedFile,
                'conditionalStylesheet' => false,
            ]);
        }

        return $this;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function generateLinks(array $items)
    {
        if (empty($items)) {
            return [];
        }

        $links = [];
        foreach ($items as $item) {
            $links[] = $this->itemToString($item);
        }

        return $links;
    }
}
