<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Reader\Loader;

use org\bovigo\vfs\vfsStream;
use Propel\Generator\Schema\Loader\XmlSchemaLoader;
use Propel\Tests\Generator\Schema\ReaderTestCase;
use Symfony\Component\Config\FileLocator;

class XmlSchemaLoaderTest extends ReaderTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->addXmlSchema();
        $this->loader = new XmlSchemaLoader(new FileLocator());
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.xml'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns false if the resource is not loadable');
        $this->assertFalse($this->loader->supports($this->getRoot()->url()), '->supports() returns false if the resource is not a string.');
    }

    public function testXmlSchemaCanBeLoaded()
    {
        $actual = $this->loader->load(vfsStream::url('root/schema.xml'));
        $this->assertEquals('bookstore', $actual['database']['name']);
        $this->assertEquals('Book', $actual['database']['entity'][0]['name']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "vfs://root/inexistent.xml" does not exist
     */
    public function testXmlFileDoesNotExist()
    {
        $this->loader->load(vfsStream::url('root/inexistent.xml'));
    }

    /**
     * @expectedException        Propel\Generator\Schema\Exception\InvalidArgumentException
     * @expectedExceptionMessage The schema file 'vfs://root/nonvalid.xml' has invalid content.
     */
    public function testXmlFileHasInvalidContent()
    {
        $content = <<<EOF
not xml content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.xml')->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/nonvalid.xml'));
    }

    /**
     * @expectedException        Propel\Generator\Schema\Exception\InvalidArgumentException
     * @expectedExceptionMessage The schema file 'vfs://root/empty.xml' has invalid content.
     */
    public function testXmlFileIsEmpty()
    {
        vfsStream::newFile('empty.xml')->at($this->getRoot())->setContent('');
        $this->loader->load(vfsStream::url('root/empty.xml'));
    }

    /**
     * @expectedException phootwork\file\exception\FileException
     * @expectedExceptionMessage You don't have permissions to access
     */
    public function testSchemaFileNotReadableThrowsException()
    {
        $content = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>
<database name="nonreadable">
    <entity name="Book" description="Book Table">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id"/>
    </entity>
</database>
EOF;

        vfsStream::newFile('notreadable.xml', 0000)->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/notreadable.xml'));
    }
}
