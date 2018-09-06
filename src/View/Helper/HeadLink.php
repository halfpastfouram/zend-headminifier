<?php

namespace Halfpastfour\HeadMinifier\View\Helper;

use MatthiasMullie\Minify;

/**
 * Class HeadLink
 *
 * @package Halfpastfour\HeadMinifier\View\Helper
 *
 * Proxies to container methods:
 * @method string|int getWhitespace(string | int $indent)
 * @method string|int getIndent()
 * @method string getSeparator()
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
    private $publicDir = '';

    /**
     * @var string
     */
    private $cacheDir = '';

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
        $this->options   = $config;
        $this->publicDir = $this->options['directories']['public'];
        $this->cacheDir  = $this->options['directories']['cache'];
        $this->baseUrl   = $baseUrl;

        parent::__construct();
    }

    /**
     * @param string|int $indent
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
        // Process all items. The items that don't require any changes will be returned in $items. The items that will
        // be cached will be returned in $cacheItems.
        $items = $this->processItems($cacheItems);

        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $identifier   = sha1(implode($cacheItems));
        $minifiedFile = "/{$identifier}.min.css";

        // Create a minified file containing all cache items. Return the name of the minified file as the last item in
        // returned in $items.
        $links = $this->minifyFile($minifiedFile, $cacheItems, $items)
            // Generate the links
                      ->generateLinks($items);

        return $indent . implode($this->escape($this->getSeparator()) . $indent, $links);
    }

    /**
     * @param array $cacheItems
     *
     * @return array
     */
    private function processItems(array &$cacheItems): array
    {
        $items = [];
        foreach ($this as $index => $item) {
            if (! $item->href || $item->type != 'text/css') {
                continue;
            }
            $localUri  = str_replace($this->baseUrl, '', preg_replace('/\?.*/', '', $this->publicDir . $item->href));
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
     * @param string $minifiedFileName
     *
     * @return string
     */
    private function generateMinifiedFilePath($minifiedFileName): string
    {
        $minifiedFilePath = $this->cacheDir . $minifiedFileName;
        if (strpos($minifiedFilePath, $this->publicDir) === 0) {
            $minifiedFilePath = substr($minifiedFilePath, strlen($this->publicDir)) ?: $minifiedFilePath;
        }

        return $this->baseUrl . $minifiedFilePath;
    }

    /**
     * @param string $minifiedFileName
     * @param array  $cacheItems
     * @param array  $items
     *
     * @return $this
     */
    private function minifyFile(string $minifiedFileName, array $cacheItems, array &$items): HeadLink
    {
        if (! empty($cacheItems)) {
            if (! is_file($this->cacheDir . $minifiedFileName)) {
                $minifier = new Minify\CSS();
                array_map(function ($uri) use ($minifier) {
                    $minifier->add($uri);
                }, $cacheItems);
                $minifier->minify($this->cacheDir . $minifiedFileName);
            }

            // Add the minified file tot the list of items.
            $items[] = $this->createData([
                'type'                  => 'text/css',
                'rel'                   => 'stylesheet',
                'href'                  => $this->generateMinifiedFilePath($minifiedFileName),
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
    private function generateLinks(array $items): array
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
