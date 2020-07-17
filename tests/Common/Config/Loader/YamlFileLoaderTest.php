<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use org\bovigo\vfs\vfsStream;
use phootwork\file\exception\FileException;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\XmlParseException;
use Propel\Common\Config\Loader\YamlFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlFileLoaderTest extends TestCase
{
    use VfsTrait;

    protected YamlFileLoader $loader;

    public function setUp(): void
    {
        parent::setUp();
        $this->loader = new YamlFileLoader(new FileLocator([$this->getRoot()->url()]));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.yaml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yaml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testYamlFileCanBeLoaded(): void
    {
        $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
        $file = $this->newFile('parameters.yaml', $content);

        $test = $this->loader->load($file->url());
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testYamlFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The file \"inexistent.yaml\" does not exist (in:");

        $this->loader->load('inexistent.yaml');
    }

    public function testYamlFileHasInvalidContent(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("The content is not valid yaml");

        $content = <<<EOF
not yaml content
only plain
text
EOF;
        $file = $this->newFile('nonvalid.yaml', $content);

        $this->loader->load($file->url());
    }

    public function testYamlFileIsEmpty(): void
    {
        $file = $this->newFile('empty.yaml', '');
        $actual = $this->loader->load($file->url());

        $this->assertEquals([], $actual);
    }

    public function testYamlFileNotReadableThrowsException(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage("You don't have permissions to access notreadable.yaml file");
        $content = <<<EOF
foo: bar
bar: baz
EOF;
        $file = $this->newFile('notreadable.yaml', $content)->chmod(200);

        $actual = $this->loader->load($file->url());
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
