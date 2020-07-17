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
use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Loader\IniFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class IniFileLoaderTest extends TestCase
{
    use VfsTrait;

    protected IniFileLoader $loader;

    public function setUp(): void
    {
        parent::setUp();
        $this->loader = new IniFileLoader(new FileLocator([$this->getRoot()->url()]));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.ini'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.ini.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testIniFileCanBeLoaded(): void
    {
        $content = <<<EOF
;test ini
foo = bar
bar = baz
EOF;
        $file = $this->newFile('parameters.ini', $content);

        $test = $this->loader->load($file->url());
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testIniFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The file \"inexistent.ini\" does not exist (in:");

        $this->loader->load('inexistent.ini');
    }

    public function testIniFileHasInvalidContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The configuration file 'vfs://root/nonvalid.ini' has invalid content.");

        $content = <<<EOF
{not ini content}
only plain
text
EOF;
        $file = $this->newFile('nonvalid.ini', $content);

        @$this->loader->load($file->url());
    }

    public function testIniFileIsEmpty(): void
    {
        $file = $this->newFile('empty.ini', '');

        $actual = $this->loader->load($file->url());

        $this->assertEquals([], $actual);
    }

    public function testWithSections(): void
    {
        $content = <<<EOF
[Cartoons]
Dog          = Pluto
Donald[]     = Huey
Donald[]     = Dewey
Donald[]     = Louie
Mickey[love] = Minnie
EOF;
        $file = $this->newFile('section.ini', $content);
        $actual = $this->loader->load($file->url());

        $this->assertEquals('Pluto', $actual['Cartoons']['Dog']);
        $this->assertEquals('Huey', $actual['Cartoons']['Donald'][0]);
        $this->assertEquals('Dewey', $actual['Cartoons']['Donald'][1]);
        $this->assertEquals('Louie', $actual['Cartoons']['Donald'][2]);
        $this->assertEquals('Minnie', $actual['Cartoons']['Mickey']['love']);
    }

    public function testNestedSections(): void
    {
        $content = <<<EOF
foo.bar.baz   = foobar
foo.bar.babaz = foobabar
bla.foo       = blafoo
bla.bar       = blabar
EOF;
        $file = $this->newFile('nested.ini', $content);
        $actual = $this->loader->load($file->url());

        $this->assertEquals('foobar', $actual['foo']['bar']['baz']);
        $this->assertEquals('foobabar', $actual['foo']['bar']['babaz']);
        $this->assertEquals('blafoo', $actual['bla']['foo']);
        $this->assertEquals('blabar', $actual['bla']['bar']);
    }

    public function testMixedNestedSections(): void
    {
        $content = <<<EOF
bla.foo.bar = foobar
bla.foobar[] = foobarArray
bla.foo.baz[] = foobaz1
bla.foo.baz[] = foobaz2

EOF;
        $file = $this->newFile('mixnested.ini', $content);
        $actual = $this->loader->load($file->url());

        $this->assertEquals('foobar', $actual['bla']['foo']['bar']);
        $this->assertEquals('foobarArray', $actual['bla']['foobar'][0]);
        $this->assertEquals('foobaz1', $actual['bla']['foo']['baz'][0]);
        $this->assertEquals('foobaz2', $actual['bla']['foo']['baz'][1]);
    }

    public function testIniFileNotReadableThrowsException(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage("You don't have permissions to access notreadable.ini file");

        $content = <<<EOF
foo = bar
bar = baz
EOF;
        $file = $this->newFile('notreadable.ini', $content)->chmod(200);

        $actual = $this->loader->load($file->url());
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
