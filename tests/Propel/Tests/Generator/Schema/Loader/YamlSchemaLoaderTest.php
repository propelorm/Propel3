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
use Propel\Generator\Schema\Loader\YamlSchemaLoader;
use Propel\Tests\Generator\Schema\ReaderTestCase;
use Symfony\Component\Config\FileLocator;

class YamlSchemaLoaderTest extends ReaderTestCase
{
    public function setUp()
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

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "vfs://root/inexistent.yaml" does not exist
     */
    public function testYamlFileDoesNotExist()
    {
        $this->loader->load(vfsStream::url('root/inexistent.yaml'));
    }

    /**
     * @expectedException        Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage The content is not valid yaml.
     */
    public function testYamlFileHasInvalidContent()
    {
        $content = <<<EOF
not yaml content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.yaml')->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/nonvalid.yaml'));
    }

    /**
     * @expectedException        Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage The content is not valid yaml.
     */
    public function testYamlFileIsEmpty()
    {
        vfsStream::newFile('empty.yaml')->at($this->getRoot())->setContent('');
        $this->loader->load(vfsStream::url('root/empty.yaml'));
    }

    /**
     * @expectedException phootwork\file\exception\FileException
     * @expectedExceptionMessage You don't have permissions to access
     */
    public function testSchemaFileNotReadableThrowsException()
    {
        $content = <<<EOF
database:
    entities:
EOF;

        vfsStream::newFile('notreadable.yaml', 0000)->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/notreadable.yaml'));
    }
}
