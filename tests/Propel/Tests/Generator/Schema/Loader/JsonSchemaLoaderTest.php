<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Schema\Loader;

use org\bovigo\vfs\vfsStream;
use Propel\Generator\Schema\Loader\JsonSchemaLoader;
use Propel\Tests\Generator\Schema\ReaderTestCase;
use Symfony\Component\Config\FileLocator;

class JsonSchemaLoaderTest extends ReaderTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->addJsonSchema();
        $this->loader = new JsonSchemaLoader(new FileLocator());
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns false if the resource is not loadable');
        $this->assertFalse($this->loader->supports($this->root->url()), '->supports() returns false if the resource is not a string.');
    }

    public function testJsonSchemaCanBeLoaded()
    {
        $actual = $this->loader->load(vfsStream::url('schema_dir/schema.json'));
        $this->assertEquals('bookstore', $actual['database']['name']);
        $this->assertEquals('Book', $actual['database']['entities'][0]['name']);
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
     * @expectedException \phootwork\json\JsonException
     * @expectedExceptionMessage Syntax error
     */
    public function testJsonFileHasInvalidContent()
    {
        $content = <<<EOF
not json content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.json')->at($this->root)->setContent($content);

        $this->loader->load(vfsStream::url('schema_dir/nonvalid.json'));
    }

    /**
     * @expectedException \phootwork\json\JsonException
     * @expectedExceptionMessage Syntax error
     */
    public function testJsonFileIsEmpty()
    {
        vfsStream::newFile('empty.json')->at($this->root)->setContent('');

        $this->loader->load(vfsStream::url('schema_dir/empty.json'));
    }

    /**
     * @expectedException \phootwork\file\exception\FileException
     * @expectedExceptionMessage You don't have permissions to access notreadable.json file
     */
    public function testJsonFileNotReadableThrowsException()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;

        vfsStream::newFile('notreadable.json', 0000)->at($this->root)->setContent($content);
        $this->loader->load(vfsStream::url('schema_dir/notreadable.json'));
    }
}
