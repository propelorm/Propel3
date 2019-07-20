<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Tests\Common\Config\Loader;

use org\bovigo\vfs\vfsStream;
use Propel\Common\Config\Loader\YamlFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class YamlFileLoaderTest extends TestCase
{
    use VfsTrait;

    /** @var  YamlFileLoader */
    protected $loader;

    public function setUp()
    {
        parent::setUp();
        $this->loader = new YamlFileLoader(new FileLocator([$this->getRoot()->url()]));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.yaml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yaml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testYamlFileCanBeLoaded()
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

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.yaml" does not exist (in:
     */
    public function testYamlFileDoesNotExist()
    {
        $this->loader->load('inexistent.yaml');
    }

    /**
     * @expectedException        Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage The content is not valid yaml
     */
    public function testYamlFileHasInvalidContent()
    {
        $content = <<<EOF
not yaml content
only plain
text
EOF;
        $file = $this->newFile('nonvalid.yaml', $content);

        $this->loader->load($file->url());
    }

    public function testYamlFileIsEmpty()
    {
        $file = $this->newFile('empty.yaml', '');
        $actual = $this->loader->load($file->url());

        $this->assertEquals([], $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file vfs://root/notreadable.yaml.
     */
    public function testYamlFileNotReadableThrowsException()
    {
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
