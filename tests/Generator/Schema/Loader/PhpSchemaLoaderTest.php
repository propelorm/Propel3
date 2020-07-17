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
use Propel\Generator\Schema\Exception\InputOutputException;
use Propel\Generator\Schema\Exception\InvalidArgumentException;
use Propel\Generator\Schema\Loader\PhpSchemaLoader;
use Propel\Tests\Generator\Schema\ReaderTestCase;
use Symfony\Component\Config\FileLocator;

class PhpSchemaLoaderTest extends ReaderTestCase
{
    private PhpSchemaLoader $loader;

    public function setUp(): void
    {
        parent::setUp();
        $this->addPhpSchema();
        $this->loader = new PhpSchemaLoader(new FileLocator());
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.php'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.inc'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports($this->getRoot()->url()), '->supports() returns false if the resource is not a string.');
    }

    public function testPhpSchemaCanBeLoaded()
    {
        $actual = $this->loader->load(vfsStream::url('root/schema.php'));
        $this->assertEquals('bookstore', $actual['database']['name']);
        $this->assertEquals('Book', $actual['database']['entities'][0]['name']);
    }

    public function testPhpFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The file \"vfs://root/inexistent.php\" does not exist");

        $this->loader->load(vfsStream::url('root/inexistent.php'));
    }

    public function testPhpFileHasInvalidContent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The schema file 'vfs://root/nonvalid.php' has invalid content.");

        $content = <<<EOF
not php content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.php')->at($this->getRoot())->setContent($content);
        $this->loader->load(vfsStream::url('root/nonvalid.php'));
    }

    public function testPhpFileIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The schema file 'vfs://root/empty.php' has invalid content.");

        vfsStream::newFile('empty.php')->at($this->getRoot())->setContent('');
        $this->loader->load(vfsStream::url('root/empty.php'));
    }

    public function testSchemaFileNotReadableThrowsException()
    {
        $this->expectException(InputOutputException::class);
        $this->expectExceptionMessage("You don't have permissions to access schema file");

        $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;

        vfsStream::newFile('notreadable.php', 0000)->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load(vfsStream::url('root/notreadable.php'));
    }
}
