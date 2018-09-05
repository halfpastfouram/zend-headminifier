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

        // Process all items. The items that don't require any changes will be returned in $items. The items that will
        // be cached will be returned in $cacheItems.
        $items = $this->processItems($publicDir, $cacheItems);

        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $identifier   = sha1(implode($cacheItems));
        $minifiedFile = "/{$identifier}.min.js";

        // Create a minified file containing all cache items. Return the name of the minified file as the last item in
        // returned in $items.
        $scripts = $this->minifyFile($minifiedFile, $cacheDir, $cacheItems, $items)
            // Generate the script tags.
                        ->generateScripts($items, $indent);

        return $indent . implode($this->escape($this->getSeparator()) . $indent, $scripts);
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
            if ($item->type !== 'text/javascript' && (! @$item->attributes['src'] || ! $item->source)) {
                $items[] = $item;
                continue;
            }
            $localUri = str_replace(
                $this->baseUrl,
                '',
                preg_replace('/\?.*/', '', $publicDir . @$item->attributes['src'])
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
     * @param string $minifiedFile
     * @param string $cacheDir
     * @param array  $cacheItems
     * @param array  $items
     *
     * @return $this
     */
    private function minifyfile($minifiedFile, $cacheDir, array $cacheItems, array &$items)
    {
        if (! is_file($cacheDir . $minifiedFile) && ! empty($cacheItems)) {
            $minifier = new Minify\JS();
            array_map(function ($uri) use ($minifier) {
                $minifier->add($uri);
            }, $cacheItems);
            $minifier->minify($cacheDir . $minifiedFile);

            $items[] = $this->createData('text/javascript', [
                'src' => $cacheDir . $minifiedFile,
            ]);
        }

        return $this;
    }

    /**
     * @return bool
     */
    private function isUseCdata()
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
    private function generateScripts(array $items, $indent)
    {
        $useCdata    = $this->isUseCdata();
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>' : '//-->';

        $scripts = [];

        foreach ($items as $item) {
            $scripts[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
        }

        // Make sure the scripts are in the correct order.
        return array_reverse($scripts, true);
    }
}
