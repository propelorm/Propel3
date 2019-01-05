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
use Propel\Common\Config\Loader\IniFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\Common\Config\ConfigTestCase;

class IniFileLoaderTest extends ConfigTestCase
{
    /** @var  IniFileLoader */
    protected $loader;

    public function setUp()
    {
        parent::setup();
        $this->loader = new IniFileLoader(new FileLocator([$this->getRoot()->url()]));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.ini'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.ini.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testIniFileCanBeLoaded()
    {
        $content = <<<EOF
;test ini
foo = bar
bar = baz
EOF;
        $file = vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);

        $test = $this->loader->load($file->url());
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.ini" does not exist (in:
     */
    public function testIniFileDoesNotExist()
    {
        $this->loader->load('inexistent.ini');
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage The configuration file 'vfs://root/nonvalid.ini' has invalid content.
     */
    public function testIniFileHasInvalidContent()
    {
        $content = <<<EOF
{not ini content}
only plain
text
EOF;
        $file = vfsStream::newFile('nonvalid.ini')->at($this->getRoot())->setContent($content);

        @$this->loader->load($file->url());
    }

    public function testIniFileIsEmpty()
    {
        $file = vfsStream::newFile('empty.ini')->at($this->getRoot())->setContent('');

        $actual = $this->loader->load($file->url());

        $this->assertEquals([], $actual);
    }

    public function testWithSections()
    {
        $content = <<<EOF
[Cartoons]
Dog          = Pluto
Donald[]     = Huey
Donald[]     = Dewey
Donald[]     = Louie
Mickey[love] = Minnie
EOF;
        $file = vfsStream::newFile('section.ini')->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load($file->url());

        $this->assertEquals('Pluto', $actual['Cartoons']['Dog']);
        $this->assertEquals('Huey', $actual['Cartoons']['Donald'][0]);
        $this->assertEquals('Dewey', $actual['Cartoons']['Donald'][1]);
        $this->assertEquals('Louie', $actual['Cartoons']['Donald'][2]);
        $this->assertEquals('Minnie', $actual['Cartoons']['Mickey']['love']);
    }

    public function testNestedSections()
    {
        $content = <<<EOF
foo.bar.baz   = foobar
foo.bar.babaz = foobabar
bla.foo       = blafoo
bla.bar       = blabar
EOF;
        $file= vfsStream::newFile('nested.ini')->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load($file->url());

        $this->assertEquals('foobar', $actual['foo']['bar']['baz']);
        $this->assertEquals('foobabar', $actual['foo']['bar']['babaz']);
        $this->assertEquals('blafoo', $actual['bla']['foo']);
        $this->assertEquals('blabar', $actual['bla']['bar']);
    }

    public function testMixedNestedSections()
    {
        $content = <<<EOF
bla.foo.bar = foobar
bla.foobar[] = foobarArray
bla.foo.baz[] = foobaz1
bla.foo.baz[] = foobaz2

EOF;
        $file = vfsStream::newFile('mixnested.ini')->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load($file->url());

        $this->assertEquals('foobar', $actual['bla']['foo']['bar']);
        $this->assertEquals('foobarArray', $actual['bla']['foobar'][0]);
        $this->assertEquals('foobaz1', $actual['bla']['foo']['baz'][0]);
        $this->assertEquals('foobaz2', $actual['bla']['foo']['baz'][1]);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file vfs://root/notreadable.ini.
     */
    public function testIniFileNotReadableThrowsException()
    {
        $content = <<<EOF
foo = bar
bar = baz
EOF;
        $file = vfsStream::newFile('notreadable.ini', 000)->at($this->getRoot())->setContent($content);

        $actual = $this->loader->load($file->url());
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
