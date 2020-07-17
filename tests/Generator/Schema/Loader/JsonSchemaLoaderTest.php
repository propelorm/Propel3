<?php declare(strict_types=1);
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
use phootwork\file\exception\FileException;
use phootwork\json\JsonException;
use Propel\Generator\Schema\Loader\JsonSchemaLoader;
use Propel\Tests\Generator\Schema\ReaderTestCase;
use Symfony\Component\Config\FileLocator;

class JsonSchemaLoaderTest extends ReaderTestCase
{
    private JsonSchemaLoader $loader;

    public function setUp(): void
    {
        parent::setUp();
        $this->addJsonSchema();
        $this->loader = new JsonSchemaLoader(new FileLocator());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns false if the resource is not loadable');
        $this->assertFalse($this->loader->supports($this->getRoot()->url()), '->supports() returns false if the resource is not a string.');
    }

    public function testJsonSchemaCanBeLoaded(): void
    {
        $actual = $this->loader->load(vfsStream::url('root/schema.json'));
        $this->assertEquals('bookstore', $actual['database']['name']);
        $this->assertEquals('Book', $actual['database']['entities'][0]['name']);
    }

    public function testJsonFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The file \"inexistent.json\" does not exist (in:");

        $this->loader->load('inexistent.json');
    }

    public function testJsonFileHasInvalidContent(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $content = <<<EOF
not json content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.json')->at($this->getRoot())->setContent($content);

        $this->loader->load(vfsStream::url('root/nonvalid.json'));
    }

    public function testJsonFileIsEmpty(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        vfsStream::newFile('empty.json')->at($this->getRoot())->setContent('');

        $this->loader->load(vfsStream::url('root/empty.json'));
    }

    public function testJsonFileNotReadableThrowsException(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('You don\'t have permissions to access notreadable.json file');
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;

        vfsStream::newFile('notreadable.json', 0000)->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/notreadable.json'));
    }
}
