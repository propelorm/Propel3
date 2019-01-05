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
use Propel\Common\Config\Loader\JsonFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\Common\Config\ConfigTestCase;

class JsonFileLoaderTest extends ConfigTestCase
{
    /** @var JsonFileLoader */
    protected $loader;

    public function setUp()
    {
        parent::setUp();
        $this->loader = new JsonFileLoader(new FileLocator([sys_get_temp_dir()]));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.json.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testJsonFileCanBeLoaded()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $file = vfsStream::newFile('parameters.json')->at($this->getRoot())->setContent($content);

        $actual = $this->loader->load($file->url());
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.json" does not exist (in:
     */
    public function testJsonFileDoesNotExist()
    {
        $this->loader->load('inexistent.json');
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\JsonParseException
     */
    public function testJsonFileHasInvalidContent()
    {
        $content = <<<EOF
not json content
only plain
text
EOF;
        $file = vfsStream::newFile('nonvalid.json')->at($this->getRoot())->setContent($content);

        $this->loader->load($file->url());
    }

    public function testJsonFileIsEmpty()
    {
        $file = vfsStream::newFile('empty.json')->at($this->getRoot())->setContent('');

        $actual = $this->loader->load($file->url());

        $this->assertEquals([], $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file vfs://root/notreadable.json.
     */
    public function testJsonFileNotReadableThrowsException()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $file = vfsStream::newFile('notreadable.json', 200)->at($this->getRoot())->setContent($content);

        $actual = $this->loader->load($file->url());

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
