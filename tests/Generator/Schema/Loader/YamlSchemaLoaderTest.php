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
use Propel\Generator\Schema\Loader\YamlSchemaLoader;
use Propel\Tests\Generator\Schema\ReaderTestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlSchemaLoaderTest extends ReaderTestCase
{
    private YamlSchemaLoader $loader;

    public function setUp(): void
    {
        parent::setUp();
        $this->addYamlSchema();
        $this->loader = new YamlSchemaLoader(new FileLocator());
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.yaml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns false if the resource is not loadable');
        $this->assertFalse($this->loader->supports($this->getRoot()->url()), '->supports() returns false if the resource is not a string.');
    }

    public function testYamlSchemaCanBeLoaded()
    {
        $actual = $this->loader->load(vfsStream::url('root/schema.yaml'));
        $this->assertEquals('bookstore', $actual['database']['name']);
        $this->assertEquals('Book', $actual['database']['entities']['Book']['name']);
    }

    public function testYamlFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "vfs://root/inexistent.yaml" does not exist');

        $this->loader->load(vfsStream::url('root/inexistent.yaml'));
    }

    public function testYamlFileHasInvalidContent()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('The content of the schema file `vfs://root/nonvalid.yaml` is not valid yaml.');

        $content = <<<EOF
not yaml content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.yaml')->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/nonvalid.yaml'));
    }

    public function testYamlFileIsEmpty()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('The content of the schema file `vfs://root/empty.yaml` is not valid yaml.');

        vfsStream::newFile('empty.yaml')->at($this->getRoot())->setContent('');
        $this->loader->load(vfsStream::url('root/empty.yaml'));
    }

    public function testSchemaFileNotReadableThrowsException()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('You don\'t have permissions to access');

        $content = <<<EOF
database:
    entities:
EOF;

        vfsStream::newFile('notreadable.yaml', 0000)->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/notreadable.yaml'));
    }
}
