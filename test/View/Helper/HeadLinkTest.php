<?php

namespace Test\View\Helper;

use Halfpastfour\HeadMinifier\View\Helper\HeadLink;
use PHPUnit\Framework\TestCase;

/**
 * Class HeadLinkTest
 *
 * @package Test\View\Helper
 */
class HeadLinkTest extends TestCase
{
    /**
     * @var HeadLink
     */
    private $helper;

    /**
     * @var array
     */
    private $config = [];

    /**
     *
     */
    public function setUp()
    {
        $this->config = [
            'enabled'     => true,
            'directories' => [
                'public' => realpath(__DIR__ . '/../../data/public'),
                'cache'  => realpath(__DIR__ . '/../../data/cache'),
            ],
        ];
        $this->helper = new HeadLink($this->config, '');
    }

    /**
     * Perform a test without any files. There shouldn't be anything in the result.
     */
    public function testNoFiles()
    {
        $helper = clone $this->helper;
        $result = $helper->toString();

        $this->assertEquals('string', gettype($result));
        $this->assertEmpty($result);
        $this->assertEmpty(glob($this->config['directories']['cache'] . '/*.min.css'));
    }

    /**
     * Test with a file. There should be exactly one element generated in the result.
     */
    public function testSingleFile()
    {
        $helper = clone $this->helper;
        $helper->appendStylesheet('/css/test1.css');

        $result      = $helper->toString();
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($result);
        /** @var \DOMNodeList $elements */
        $elements = $domDocument->getElementsByTagName('link');

        $this->assertEquals(1, $elements->length);
        $this->assertNotEmpty(glob($this->config['directories']['cache'] . '/*.min.css'));
    }

    /**
     * Test with a few files. There should be exactly one element generated in the result.
     * @depends testSingleFile
     */
    public function testMultipleFiles()
    {
        $helper = clone $this->helper;
        $helper->appendStylesheet('/css/test1.css')
               ->appendStylesheet('/css/test2.css');

        $result      = $helper->toString();
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($result);
        /** @var \DOMNodeList $elements */
        $elements = $domDocument->getElementsByTagName('link');

        $this->assertEquals(1, $elements->length);
        $this->assertNotEmpty(glob($this->config['directories']['cache'] . '/*.min.css'));
    }

    /**
     * Test with a few files. The files should appear in the correct order in the cached file.
     * @depends testMultipleFiles
     */
    public function testFilesOrder()
    {
        $helper = clone $this->helper;
        $helper->appendStylesheet('/css/test1.css')
               ->prependStylesheet('/css/test2.css');

        $result      = $helper->toString();
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($result);
        /** @var \DOMNode $element */
        $element      = $domDocument->getElementsByTagName('link')->item(0);
        $minifiedFile = $element->attributes->getNamedItem('href')->nodeValue;
        $this->assertNotEmpty($minifiedFile);
        $this->assertFileExists($minifiedFile);

        $contents              = file_get_contents($minifiedFile);
        $firstFilePlaceholder  = '.file1';
        $secondFilePlaceholder = '.file2';
        $this->assertGreaterThan($firstFilePlaceholder, $secondFilePlaceholder);
    }

    /**
     * Delete generated cache files.
     */
    public function tearDown()
    {
        foreach (glob($this->config['directories']['cache'] . '/*.min.css') as $file) {
            unlink($file);
        }
    }
}
