<?php

namespace Halfpastfour\HeadMinifier\View\Helper;

use MatthiasMullie\Minify;

/**
 * Class HeadScript
 *
 * @package Halfpastfour\HeadMinifier\View\Helper
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

        $items      = [];
        $cacheItems = [];
        $publicDir  = $this->options['directories']['public'];
        $cacheDir   = $this->options['directories']['cache'];
        $jsDir      = str_replace($publicDir, '', $cacheDir);
        foreach ($this as $item) {
            if ($item->type !== 'text/javascript' && (! @$item->attributes['src'] || ! $item->source)) {
                $items[] = $item;
                continue;
            }
            $localUri  = str_replace(
                $this->baseUrl,
                '',
                preg_replace('/\?.*/', '', $publicDir . @$item->attributes['src'])
            );
            $remoteUri = @$item->attributes['src'];
            $handle    = curl_init($remoteUri);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            if (is_file($localUri)) {
                $cacheItems[] = $localUri;
            } elseif (curl_exec($handle) !== false) {
                $cacheItems[] = $remoteUri;
            } elseif ($remoteUri || $item->source) {
                $items[] = $item;
            }
        }

        $identifier   = sha1(implode($cacheItems));
        $minifiedFile = "/{$identifier}.min.js";
        if (! is_file($minifiedFile)) {
            $minifier = new Minify\JS();
            array_map(function ($uri) use ($minifier) {
                $minifier->add($uri);
            }, $cacheItems);
            $contents = $minifier->minify($cacheDir . $minifiedFile);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        if ($this->view) {
            /** @noinspection PhpUndefinedMethodInspection */
            $useCdata = $this->view->plugin('doctype')->isXhtml();
        } else {
            $useCdata = $this->useCdata;
        }

        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>' : '//-->';

        $scripts = [
            $this->itemToString($this->createData('text/javascript', [
                'src' => $this->baseUrl . $jsDir . $minifiedFile,
            ]), $indent, $escapeStart, $escapeEnd),
        ];

        foreach ($items as $item) {
            $scripts[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        return $indent . implode($this->escape($this->getSeparator()) . $indent, $scripts);
    }
}
