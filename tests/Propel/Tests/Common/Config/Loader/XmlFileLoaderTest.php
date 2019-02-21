<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use org\bovigo\vfs\vfsStream;
use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\XmlFileLoader;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class XmlFileLoaderTest extends TestCase
{
    use VfsTrait;

    /** @var XmlFileLoader */
    protected $loader;

    public function setUp()
    {
        parent::setUp();
        $this->loader = new XmlFileLoader(new FileLocator([$this->getRoot()->url()]));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.xml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.xml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testXmlFileCanBeLoaded()
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        $file = $this->newFile('parameters.xml', $content);

        $test = $this->loader->load($file->url());
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.xml" does not exist (in:
     */
    public function testXmlFileDoesNotExist()
    {
        $this->loader->load('inexistent.xml');
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     */
    public function testXmlFileHasInvalidContent()
    {
        $content = <<<EOF
not xml content
only plain
text
EOF;
        $file = $this->newFile('nonvalid.xml', $content);

        @$this->loader->load($file->url());
    }

    public function testXmlFileIsEmpty()
    {
        $file = $this->newFile('empty.xml', '');
        $actual = $this->loader->load($file->url());

        $this->assertEquals([], $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file vfs://root/notreadable.xml.
     */
    public function testXmlFileNotReadableThrowsException()
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        $file = $this->newFile('notreadable.xml', $content)->chmod(200);

        $actual = $this->loader->load($file->url());
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
