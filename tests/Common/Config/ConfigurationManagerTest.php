<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Tests\Common\Config;

use org\bovigo\vfs\vfsStream;
use Propel\Common\Config\ConfigurationManager;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

/**
 * Class ConfigurationManagerTest
 */
class ConfigurationManagerTest extends TestCase
{
    use VfsTrait;

    public function testLoadConfigFileInCurrentDirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    public function testLoadConfigFileInConfigSubdirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('config/propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    public function testLoadConfigFileInConfSubdirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('conf/propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    public function testNotExistingConfigFileLoadsDefaultSettingsAndDoesNotThrowExceptions()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('doctrine.yaml', $yamlConf);
        try {
            $manager = new TestableConfigurationManager();
            $this->assertNotNull($manager, 'Manager loaded');
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testBackupConfigFilesAreIgnored()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('propel.yaml.bak', $yamlConf);
        $this->newFile('propel.yaml~', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertArrayNotHasKey('bar', $actual);
        $this->assertArrayNotHasKey('baz', $actual);
    }

    public function testUnsupportedExtensionsAreIgnored()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('propel.log', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertArrayNotHasKey('bar', $actual);
        $this->assertArrayNotHasKey('baz', $actual);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @exceptionMessage Propel expects only one configuration file
     */
    public function testMoreThanOneConfigurationFileInSameDirectoryThrowsException()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $iniConf = <<<EOF
foo = bar
bar = baz
EOF;
        $this->newFile('propel.yaml', $yamlConf);
        $this->newFile('propel.ini', $iniConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @exceptionMessage Propel expects only one configuration file
     */
    public function testMoreThanOneConfigurationFileInDifferentDirectoriesThrowsException()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $iniConf = <<<EOF
foo = bar
bar = baz
EOF;
        $this->newFile('propel.yaml', $yamlConf);
        $this->newFile('conf/propel.ini', $iniConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
    }

    public function testGetSection()
    {
        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->getSection('buildtime');

        $this->assertEquals('bbar', $actual['bfoo']);
        $this->assertEquals('bbaz', $actual['bbar']);
    }

    public function testLoadGivenConfigFile()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $file = $this->newFile('myDir/mySubdir/myConfigFile.yaml', $yamlConf);

        $manager = new TestableConfigurationManager($file->url());
        $actual = $manager->get();

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual);
    }

    public function testLoadAlsoDistConfigFile()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;

        $this->newFile('propel.yaml.dist', $yamlDistConf);
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertEquals(['bfoo' => 'bbar', 'bbar' => 'bbaz'], $actual['buildtime']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual['runtime']);
    }

    public function testLoadOnlyDistFile()
    {
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;

        $this->newFile('propel.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertEquals(['runtime' => ['foo' => 'bar', 'bar' => 'baz']], $actual);
    }

    public function testLoadGivenFileAndDist()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $file = $this->newFile('myDir/mySubdir/myConfigFile.yaml', $yamlConf);
        $this->newFile('myDir/mySubdir/myConfigFile.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager($file->url());
        $actual = $manager->get();

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual['runtime']);
        $this->assertEquals(['bfoo' => 'bbar', 'bbar' => 'bbaz'], $actual['buildtime']);
    }

    public function testLoadDistGivenFileOnly()
    {
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $file = $this->newFile('myDir/mySubdir/myConfigFile.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager($file->url());
        $actual = $manager->get();

        $this->assertEquals(['runtime' => ['foo' => 'bar', 'bar' => 'baz']], $actual);
    }

    public function testLoadInGivenDirectory()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $this->newFile('myDir/mySubdir/propel.yaml', $yamlConf);
        $this->newFile('myDir/mySubdir/propel.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager(vfsStream::url('root/myDir/mySubdir/'));
        $actual = $manager->get();

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $actual['runtime']);
        $this->assertEquals(['bfoo' => 'bbar', 'bbar' => 'bbaz'], $actual['buildtime']);
    }

    public function testMergeExtraProperties()
    {
        $extraConf = [
            'buildtime' => [
                'bfoo' => 'extrabar'
            ],
            'extralevel' => [
                'extra1' => 'val1',
                'extra2' => 'val2'
            ]
        ];

        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager($this->getRoot()->url(), $extraConf);
        $actual = $manager->get();

        $this->assertEquals($actual['runtime'], ['foo' => 'bar', 'bar' => 'baz']);
        $this->assertEquals($actual['buildtime'], ['bfoo' => 'extrabar', 'bbar' => 'bbaz']);
        $this->assertEquals($actual['extralevel'], ['extra1' => 'val1', 'extra2' => 'val2']);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized options "foo, bar" under "propel"
     */
    public function testInvalidHierarchyTrowsException()
    {
        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager($this->getRoot()->url());
    }

    public function testNotDefineRuntimeAndGeneratorSectionUsesDefaultConnections()
    {
        $yamlConf = <<<EOF
propel:
  general:
      project: MyAwesomeProject
      version: 2.0.0-dev
  database:
    connections:
        default:
            adapter: sqlite
            classname: Propel\Runtime\Connection\ConnectionWrapper
            dsn: sqlite:memory
            user:
            password:
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager($this->getRoot()->url());

        $this->assertArrayHasKey('runtime', $manager->get());
        $this->assertArrayHasKey('generator', $manager->get());

        $this->assertArrayHasKey('connections', $manager->getSection('runtime'));
        $this->assertArrayHasKey('connections', $manager->getSection('generator'));

        $this->assertEquals(['default'], $manager->get()['runtime']['connections']);
        $this->assertEquals(['default'], $manager->get()['generator']['connections']);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Dots are not allowed in connection names
     */
    public function testDotInConnectionNamesArentAccepted()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource.name:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager($this->getRoot()->url());
    }

    public function testLoadValidConfigurationFile()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager($this->getRoot()->url());
        $actual = $manager->getSection('runtime');

        $this->assertEquals($actual['defaultConnection'], 'mysource');
        $this->assertEquals($actual['connections'], ['mysource', 'yoursource']);
    }

    public function testSomeDeafults()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager($this->getRoot()->url());
        $actual = $manager->get();

        $this->assertEquals($actual['generator']['dateTime']['dateTimeClass'], 'DateTime');
        $this->assertEquals($actual['generator']['objectModel']['pluralizerClass'], '\Propel\Common\Pluralizer\StandardEnglishPluralizer');
    }

    public function testGetConfigProperty()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager($this->getRoot()->url());
        $this->assertEquals('mysource', $manager->getConfigProperty('runtime.defaultConnection'));
        $this->assertEquals('yoursource', $manager->getConfigProperty('runtime.connections.1'));
        $this->assertEquals('root', $manager->getConfigProperty('database.connections.mysource.user'));
    }

    public function testGetConfigPropertyBadName()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager($this->getRoot()->url());
        $value = $manager->getConfigProperty('database.connections.adapter');

        $this->assertNull($value);
    }

    public function testProcessWithParam()
    {
        $configs = [
            'propel' => [
                'database' => [
                    'connections' => [
                        'default' => [
                            'adapter' => 'sqlite',
                            'classname' => 'Propel\Runtime\Connection\DebugPDO',
                            'dsn' => 'sqlite::memory:',
                            'user' => '',
                            'password' => ''
                        ]
                    ]
                ],
                'runtime' => [
                    'defaultConnection' => 'default',
                    'connections' => ['default']
                ],
                'generator' => [
                    'defaultConnection' => 'default',
                    'connections' => ['default']
                ]
            ]
        ];

        $manager = new NotLoadingConfigurationManager($configs);
        $actual = $manager->getSection('database')['connections'];

        $this->assertEquals($configs['propel']['database']['connections'], $actual);
    }

    public function testProcessWrongParameter()
    {
        $manager = new NotLoadingConfigurationManager(null);

        $this->assertNotEmpty($manager->get());
    }

    public function testGetConfigurationParametersArrayTest()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
EOF;
        $this->newFile('propel.yaml', $yamlConf);

        $expectedRuntime = [
            'mysource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=mydb',
                'user' => 'root',
                'password' => ''
            ],
            'yoursource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=yourdb',
                'user' => 'root',
                'password' => ''
            ]
        ];

        $expectedGenerator = [
            'mysource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=mydb',
                'user' => 'root',
                'password' => ''
            ]
        ];

        $manager = new ConfigurationManager($this->getRoot()->url());
        $this->assertEquals($expectedRuntime, $manager->getConnectionParametersArray('runtime'));
        $this->assertEquals($expectedRuntime, $manager->getConnectionParametersArray()); //default `runtime`
        $this->assertEquals($expectedGenerator, $manager->getConnectionParametersArray('generator'));
        $this->assertNull($manager->getConnectionParametersArray('bad_section'));
    }
}

class TestableConfigurationManager extends ConfigurationManager
{
    public function __construct($filename = 'propel', $extraConf = [])
    {
        $this->load($filename, $extraConf);
    }
}

class NotLoadingConfigurationManager extends ConfigurationManager
{
    public function __construct($configs = null)
    {
        $this->process($configs);
    }
}
