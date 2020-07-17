<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use phootwork\file\exception\FileException;
use phootwork\json\JsonException;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Loader\JsonFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class JsonFileLoaderTest extends TestCase
{
    use VfsTrait;
    
    protected JsonFileLoader $loader;

    public function setUp(): void
    {
        parent::setUp();
        $this->loader = new JsonFileLoader(new FileLocator([$this->getRoot()->url()]));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.json.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testJsonFileCanBeLoaded(): void
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $file = $this->newFile('parameters.json', $content);

        $actual = $this->loader->load($file->url());
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
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

        $content = <<<EOF
not json content
only plain
text
EOF;
        $file = $this->newFile('nonvalid.json', $content);

        $this->loader->load($file->url());
    }

    public function testJsonFileIsEmpty(): void
    {
        $file = $this->newFile('empty.json', '');

        $actual = $this->loader->load($file->url());

        $this->assertEquals([], $actual);
    }

    public function testJsonFileNotReadableThrowsException(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage("You don't have permissions to access notreadable.json file")
        ;
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $file = $this->newFile('notreadable.json', $content)->chmod(200);

        $actual = $this->loader->load($file->url());

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
