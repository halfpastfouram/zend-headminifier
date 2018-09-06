<?php

namespace Halfpastfour\HeadMinifier\View\Helper;

use MatthiasMullie\Minify;
use Zend\View\Renderer\PhpRenderer;

/**
 * Class HeadScript
 *
 * @package Halfpastfour\HeadMinifier\View\Helper
 *
 * Proxies to container methods:
 * @method string|int getWhitespace(string | int $indent)
 * @method string|int getIndent()
 * @method string getSeparator()
 */
class HeadScript extends \Zend\View\Helper\HeadScript
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
     * @param string $indent
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
        $minifiedFile = "/{$identifier}.min.js";

        // Create a minified file containing all cache items. Return the name of the minified file as the last item in
        // returned in $items.
        $scripts = $this->minifyFile($minifiedFile, $cacheItems, $items)
            // Generate the script tags.
                        ->generateScripts($items, $indent);

        return $indent . implode($this->escape($this->getSeparator()) . $indent, $scripts);
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
            if ($item->type !== 'text/javascript' && (! @$item->attributes['src'] || ! $item->source)) {
                $items[] = $item;
                continue;
            }
            $localUri = str_replace(
                $this->baseUrl,
                '',
                preg_replace('/\?.*/', '', $this->publicDir . @$item->attributes['src'])
            );

            $remoteUri = @$item->attributes['src'];
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

            if ($remoteUri || $item->source) {
                $items[$index] = $item;
            }
        }

        return $items;
    }

    /**
     * @param string $minifiedFileName
     *
     * @return string
     */
    private function generateMinifiedFilePath(string $minifiedFileName): string
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
    private function minifyFile(string $minifiedFileName, array $cacheItems, array &$items): HeadScript
    {
        if (! empty($cacheItems)) {
            if (! is_file($this->cacheDir . $minifiedFileName)) {
                $minifier = new Minify\JS();
                array_map(function ($uri) use ($minifier) {
                    $minifier->add($uri);
                }, $cacheItems);
                $minifier->minify($this->cacheDir . $minifiedFileName);
            }

            // Add the minified file to the list of items.
            $items[] = $this->createData('text/javascript', [
                'src' => $this->generateMinifiedFilePath($minifiedFileName),
            ]);
        }

        return $this;
    }

    /**
     * @return bool
     */
    private function isUseCdata(): bool
    {
        $view     = $this->view;
        $useCdata = $this->useCdata;
        if ($view instanceof PhpRenderer) {
            /** @var \Zend\View\Helper\Doctype $plugin */
            $plugin   = $view->plugin('doctype');
            $useCdata = $plugin->isXhtml();
        }

        return $useCdata;
    }

    /**
     * @param array  $items
     * @param string $indent
     *
     * @return array
     */
    private function generateScripts(array $items, $indent): array
    {
        $useCdata    = $this->isUseCdata();
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>' : '//-->';

        $scripts = [];

        foreach ($items as $item) {
            $scripts[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
        }

        return $scripts;
    }
}
