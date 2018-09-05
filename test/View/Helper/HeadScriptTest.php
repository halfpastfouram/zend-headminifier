<?php

namespace Test\View\Helper;

use Halfpastfour\HeadMinifier\View\Helper\HeadScript;
use PHPUnit\Framework\TestCase;

/**
 * Class HeadScriptTest
 *
 * @package Test\View\Helper
 */
class HeadScriptTest extends TestCase
{
    /**
     * @var HeadScript
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
        $this->helper = new HeadScript($this->config, '');
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
        $this->assertEmpty(glob($this->config['directories']['cache'] . '/*.min.js'));
    }

    /**
     * Test with a file. There should be exactly one element generated in the result.
     */
    public function testSingleFile()
    {
        $helper = clone $this->helper;
        $helper->appendFile('/js/test1.js');

        $result      = $helper->toString();
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($result);
        /** @var \DOMNodeList $elements */
        $elements = $domDocument->getElementsByTagName('script');

        $this->assertEquals(1, $elements->length);
        $this->assertNotEmpty(glob($this->config['directories']['cache'] . '/*.min.js'));
    }

    /**
     * Test with a few files. There should be exactly one element generated in the result.
     * @depends testSingleFile
     */
    public function testMultipleFiles()
    {
        $helper = clone $this->helper;
        $helper->appendFile('/js/test1.js')
               ->appendFile('/js/test2.js');

        $result      = $helper->toString();
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($result);
        /** @var \DOMNodeList $elements */
        $elements = $domDocument->getElementsByTagName('script');

        $this->assertEquals(1, $elements->length);
        $this->assertNotEmpty(glob($this->config['directories']['cache'] . '/*.min.js'));
    }

    /**
     * Test with a few files. The files should appear in the correct order in the cached file.
     * @depends testMultipleFiles
     */
    public function testFilesOrder()
    {
        $helper = clone $this->helper;
        $helper->appendFile('/js/test1.js')
               ->prependFile('/js/test2.js');

        $result      = $helper->toString();
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($result);

        /** @var \DOMNode $element */
        $element      = $domDocument->getElementsByTagName('script')->item(0);
        $minifiedFile = $element->attributes->getNamedItem('src')->nodeValue;
        $this->assertNotEmpty($minifiedFile);
        $this->assertFileExists($minifiedFile);

        $contents              = file_get_contents($minifiedFile);
        $firstFilePlaceholder  = 'firstFile()';
        $secondFilePlaceholder = 'secondFile()';
        $this->assertLessThan(strpos($contents, $firstFilePlaceholder), strpos($contents, $secondFilePlaceholder));
    }

    /**
     * Delete generated cache files.
     */
    public function tearDown()
    {
        foreach (glob($this->config['directories']['cache'] . '/*.min.js') as $file) {
            unlink($file);
        }
    }
}
