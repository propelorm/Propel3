<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Cristiano Cinotti
 * @package	propel.generator.config
 */
class GeneratorConfigTest extends TestCase
{
    use VfsTrait;

    /**
     * @var GeneratorConfig
     */
    protected $generatorConfig;

    public function setConfig($config)
    {
        $ref = new \ReflectionClass('\\Propel\\Common\\Config\\ConfigurationManager');
        $refProp = $ref->getProperty('config');
        $refProp->setAccessible(true);
        $refProp->setValue($this->generatorConfig, $config);
    }

    public function setUp()
    {
        $php = "
<?php
    return array(
        'propel' => array(
            'database' => array(
                'connections' => array(
                    'mysource' => array(
                        'adapter' => 'sqlite',
                        'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                        'dsn' => 'sqlite:" . sys_get_temp_dir() . "/mydb',
                        'user' => 'root',
                        'password' => ''
                    ),
                    'yoursource' => array(
                        'adapter' => 'mysql',
                        'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                        'dsn' => 'mysql:host=localhost;dbname=yourdb',
                        'user' => 'root',
                        'password' => ''
                    )
                )
            ),
            'runtime' => array(
                'defaultConnection' => 'mysource',
                'connections' => array('mysource', 'yoursource')
            ),
            'generator' => array(
                'defaultConnection' => 'mysource',
                'connections' => array('mysource', 'yoursource')
            )
        )
);
";
        $file = $this->newFile('propel.php.dist', $php);

        $this->generatorConfig = new GeneratorConfig($file->url());
    }

    public function testGetConfiguredPlatformDeafult()
    {
        $actual = $this->generatorConfig->createPlatformForDatabase('yoursource');

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\MysqlPlatform', $actual);
    }

    public function testGetConfiguredPlatformGivenDatabaseName()
    {
        $actual = $this->generatorConfig->createPlatformForDatabase('mysource');

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\SqlitePlatform', $actual);
    }

    public function testGetConfiguredPlatform()
    {
        $this->setConfig(['generator' => ['platformClass' => '\\Propel\\Generator\\Platform\\PgsqlPlatform']]);
        $actual = $this->generatorConfig->createPlatformForDatabase();
        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\PgsqlPlatform', $actual);
    }

    /**
     * @expectedException Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid database name: no configured connection named `badsource`.
     */
    public function testGetConfiguredPlatformGivenBadDatabaseNameThrowsException()
    {
        $this->generatorConfig->createPlatformForDatabase('badsource');
    }

    public function testGetConfiguredPlatformGivenPlatform()
    {
        $this->setConfig(['generator' => ['platformClass' => '\\Propel\\Generator\\Platform\\PgsqlPlatform']]);
        $actual = $this->generatorConfig->createPlatformForDatabase();

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\PgsqlPlatform', $actual);
    }

    public function testGetConfiguredSchemaParserDefaultClass()
    {
        $stubCon = $this->getMockBuilder('\\Propel\\Runtime\\Connection\\ConnectionWrapper')
            ->disableOriginalConstructor()->getMock();

        $actual = $this->generatorConfig->getConfiguredSchemaParser($stubCon);

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\SqliteSchemaParser', $actual);
    }

    public function testGetConfiguredSchemaParserGivenClass()
    {
        $this->setConfig(
            ['migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Reverse\\PgsqlSchemaParser'
            ]]
        );
        $stubCon = $this->getMockBuilder('\\Propel\\Runtime\\Connection\\ConnectionWrapper')
            ->disableOriginalConstructor()->getMock();

        $actual = $this->generatorConfig->getConfiguredSchemaParser($stubCon);

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    /**
     * @expectedException \Propel\Generator\Exception\BuildException
     * @expectedExceptionMessage Specified class (\Propel\Generator\Platform\MysqlPlatform) does not implement \Propel\Generator\Reverse\SchemaParserInterface interface.
     */
    public function testGetConfiguredSchemaParserGivenNonSchemaParserClass()
    {
        $this->setConfig(
            ['migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Platform\\MysqlPlatform'
            ]]
        );

        $actual = $this->generatorConfig->getConfiguredSchemaParser();

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    /**
     * @expectedException \Propel\Generator\Exception\ClassNotFoundException
     * @expectedExceptionMessage Class \Propel\Generator\Reverse\BadSchemaParser not found.
     */
    public function testGetConfiguredSchemaParserGivenBadClass()
    {
        $this->setConfig(
            ['migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Reverse\\BadSchemaParser'
            ]]
        );

        $actual = $this->generatorConfig->getConfiguredSchemaParser();

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    public function testGetConfiguredBuilder()
    {
        $stubEntity = $this->createMock('\\Propel\\Generator\\Model\\Entity');
        $actual = $this->generatorConfig->getConfiguredBuilder($stubEntity, 'query');

        $this->assertInstanceOf('\\Propel\\Generator\\Builder\\Om\\QueryBuilder', $actual);
    }

    /**
     * @expectedException \Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Builder for `bad_type` not found.
     */
    public function testGetConfiguredBuilderWrongTypeThrowsException()
    {
        $stubEntity = $this->createMock('\\Propel\\Generator\\Model\\Entity');
        $actual = $this->generatorConfig->getConfiguredBuilder($stubEntity, 'bad_type');
    }

    public function testGetConfiguredPluralizer()
    {
        $actual = $this->generatorConfig->getConfiguredPluralizer();
        $this->assertInstanceOf('\\Propel\\Common\\Pluralizer\\StandardEnglishPluralizer', $actual);

        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Pluralizer\\SimpleEnglishPluralizer';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
        $this->assertInstanceOf('\\Propel\\Common\\Pluralizer\\SimpleEnglishPluralizer', $actual);
    }

    /**
     * @expectedException Propel\Generator\Exception\ClassNotFoundException
     * @expectedExceptionMessage Class \Propel\Common\Pluralizer\WrongEnglishPluralizer not found.
     */
    public function testGetConfiguredPluralizerNonExistentClassThrowsException()
    {
        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Pluralizer\\WrongEnglishPluralizer';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
    }

    /**
     * @expectedException \Propel\Generator\Exception\BuildException
     * @expectedExceptionMessage Specified class (\Propel\Common\Config\PropelConfiguration) does not implement
     */
    public function testGetConfiguredPluralizerWrongClassThrowsException()
    {
        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Config\\PropelConfiguration';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
    }

    public function testGetBuildConnections()
    {
        $expected = [
            'mysource' => [
                'adapter' => 'sqlite',
                'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                'dsn' => 'sqlite:' . sys_get_temp_dir() . '/mydb',
                'user' => 'root',
                'password' => ''
            ],
            'yoursource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=yourdb',
                'user' => 'root',
                'password' => ''
            ]
        ];

        $actual = $this->generatorConfig->getBuildConnections();

        $this->assertEquals($expected, $actual);
    }

    public function testGetBuildConnection()
    {
        $expected = [
            'adapter' => 'sqlite',
            'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
            'dsn' => 'sqlite:' . sys_get_temp_dir() . '/mydb',
            'user' => 'root',
            'password' => ''
        ];

        $actual = $this->generatorConfig->getBuildConnection();

        $this->assertEquals($expected, $actual);
    }

    public function testGetBuildConnectionGivenDatabase()
    {
        $expected = [
            'adapter' => 'mysql',
            'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
            'dsn' => 'mysql:host=localhost;dbname=yourdb',
            'user' => 'root',
            'password' => ''
        ];

        $actual = $this->generatorConfig->getBuildConnection('yoursource');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid database name: no configured connection named `wrongsource`.
     */
    public function testGetBuildConnectionGivenWrongDatabaseThrowsException()
    {
        $actual = $this->generatorConfig->getBuildConnection('wrongsource');
    }

    public function testGetConnectionDefault()
    {
        $actual = $this->generatorConfig->getConnection();

        $this->assertInstanceOf('\\Propel\\Runtime\\Connection\\ConnectionWrapper', $actual);
    }

    public function testGetConnection()
    {
        $actual = $this->generatorConfig->getConnection('mysource');

        $this->assertInstanceOf('\\Propel\\Runtime\\Connection\\ConnectionWrapper', $actual);
    }

    /**
     * @expectedException \Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid database name: no configured connection named `badsource`.
     */
    public function testGetConnectionWrongDatabaseThrowsException()
    {
        $actual = $this->generatorConfig->getConnection('badsource');
    }

    public function testGetBehaviorManager()
    {
        $actual = $this->generatorConfig->getBehaviorManager();

        $this->assertInstanceOf('\\Propel\\Generator\\Manager\\BehaviorManager', $actual);
    }
}
