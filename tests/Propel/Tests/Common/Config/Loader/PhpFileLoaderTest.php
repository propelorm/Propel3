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
use Propel\Common\Config\Loader\PhpFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class PhpFileLoaderTest extends TestCase
{
    use VfsTrait;

    /** @var PhpFileLoader */
    protected $loader;

    public function setUp()
    {
        parent::setUp();
        $this->loader = new PhpFileLoader(new FileLocator([$this->getRoot()->url()]));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.php'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.inc'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.php.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.inc.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testPhpFileCanBeLoaded()
    {
        $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;
        $file = $this->newFile('parameters.php', $content);
        $test = $this->loader->load($file->url());
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.php" does not exist (in:
     */
    public function testPhpFileDoesNotExist()
    {
        $this->loader->load('inexistent.php');
    }

    /**
    * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
    * @expectedExceptionMessage The configuration file 'vfs://root/nonvalid.php' has invalid content.
    */
    public function testPhpFileHasInvalidContent()
    {
        $content = <<<EOF
not php content
only plain
text
EOF;
        $file = $this->newFile('nonvalid.php', $content);
        $this->loader->load($file->url());
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage The configuration file 'vfs://root/empty.php' has invalid content.
     */
    public function testPhpFileIsEmpty()
    {
        $file = $this->newFile('empty.php', '');

        $this->loader->load($file->url());
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file vfs://root/notreadable.php.
     */
    public function testConfigFileNotReadableThrowsException()
    {
        $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;
        $file = $this->newFile('notreadable.php', $content)->chmod(200);

        $actual = $this->loader->load($file->url());
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
