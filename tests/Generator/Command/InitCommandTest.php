<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\DatabaseReverseCommand;
use Propel\Generator\Command\InitCommand;
use Propel\Tests\TestCaseFixtures;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 *
 * @group database
 */
class InitCommandTest extends TestCaseFixtures
{
    /** @var  InitCommand */
    private $command;
    private $tempDir;
    private $currentDir;

    /** @var  Filesystem */
    private $fileSystem;

    public function setUp()
    {
        parent::setUp();

        $this->fileSystem = new Filesystem();
        $this->command = new InitCommand();
        $this->tempDir = sys_get_temp_dir() . '/init_command/';
        $this->cleanupTempDir();
        $this->currentDir = getcwd();
        chdir($this->tempDir);
    }

    public function tearDown()
    {
        chdir($this->currentDir);
        parent::tearDown();
    }

    public function testExecuteOk()
    {
        //Create the array of answers
        $answers['driver'] = $driver = $this->getDriver();
        if ('sqlite' == $driver) {
            $answers['db_dir'] = $this->tempDir;
        } else {
            $answers['hostname'] = getenv('DB_HOSTNAME') ?: 'localhost';
            $answers['port'] = null;
            $answers['dbname'] = getenv('DB_NAME') ?: 'test';
        }
        $answers['user'] = getenv('DB_USER') ?: 'root';
        $answers['pwd'] = getenv('DB_PW') ?: '';
        $answers['charset'] = null;
        $answers['database'] = 'no'; //existing database
        $answers['schema_dir'] = $this->tempDir;
        $answers['php_dir'] = $this->tempDir;
        $answers['namespace'] = 'myNamespace';
        $answers['format'] = 'xml';
        $answers['confirm'] = 'yes'; //confirm every answer

        $app = new Application();
        $app->add($this->command);

        $tester = new CommandTester($app->find('init'));
        $tester->setInputs($answers);
        $tester->execute(['command' => $this->command->getName()]);
        $output = $tester->getDisplay();

        $this->assertContains("Propel 3 Initializer\n====================", $output, 'Display the title');
        $this->assertContains('Please pick your favorite database management system', $output, 'Display dbms question.');
        $this->assertContains('Please enter your database user', $output, 'Display database user question.');
        $this->assertContains('[root]', $output, 'Display database default user.');
        $this->assertContains('Please enter your database password', $output, 'Display database password question.');
        $this->assertContains('Which charset would you like to use?', $output, 'Display database charset question.');
        $this->assertContains('[utf8]', $output, 'Display database default charset.');
        $this->assertContains('Please enter the format to use', $output, 'Display configuration file format question.');
        $this->assertContains('[yml]', $output, 'Display configuration file default format.');
        $this->assertContains('Connected to sql server successful!', $output);

        $this->assertFileExists($this->tempDir . '/propel.xml', 'Configuration file created.');
        $this->assertFileExists($this->tempDir . '/propel.xml.dist', 'Configuration dist file created.');
        $this->assertFileExists($this->tempDir . '/schema.xml', 'Schema file created.');

        $configContent = file_get_contents($this->tempDir . '/propel.xml');
        $this->assertContains('<adapter>mysql</adapter>', $configContent, 'Config file contains adapter information.');
        $this->assertContains('<dsn>mysql:host=localhost;port=3306;dbname=test</dsn>', $configContent, 'Config file contains dsn information.');

        $distContent = file_get_contents($this->tempDir . '/propel.xml.dist');
        $this->assertContains('<schemaDir>/tmp/init_command/</schemaDir>', $distContent);
        $this->assertContains('<phpDir>/tmp/init_command/</phpDir>', $distContent);
        $this->assertContains(
            '<database>
            <connections>
                <connection id="default">
                    <adapter>mysql</adapter>
                    <dsn>mysql:host=localhost;port=3306;dbname=test</dsn>',
            $distContent
        );
        $this->assertContains(
            '<password></password>
                    <settings>
                        <charset>utf8</charset>
                    </settings>
                </connection>
            </connections>
        </database>',
            $distContent
        );

        $stubContent = '<entity name="book" phpName="Book">
        <!--
            Each column has a `name` (the one used by the database), and an optional `phpName` attribute. Once again,
            the Propel default behavior is to use a CamelCase version of the name as `phpName` when not specified.

            Each column also requires a `type`. The XML schema is database agnostic, so the column types and attributes
            are probably not exactly the same as the one you use in your own database. But Propel knows how to map the
            schema types with SQL types for many database vendors. Existing Propel column types are:
            `boolean`, `tinyint`, `smallint`, `integer`, `bigint`, `double`, `float`, `real`, `decimal`, `char`,
            `varchar`, `longvarchar`, `date`, `time`, `timestamp`, `blob`, `clob`, `object`, and `array`.

            Some column types use a size (like `varchar` and `int`), some have unlimited size (`longvarchar`, `clob`,
            `blob`).

            Check the (schema reference)[http://propelorm.org/reference/schema.html] for more details
            on each column type.

            As for the other column attributes, `required`, `primaryKey`, and `autoIncrement`, they mean exactly
            what their names imply.
        -->
        <field name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <field name="title" type="varchar" size="255" required="true"/>
        <field name="isbn" type="varchar" size="24" required="true" phpName="ISBN"/>
        <field name="publisher_id" type="integer" required="true"/>
        <field name="author_id" type="integer" required="true"/>

        <!--
            A foreign key represents a relationship. Just like a table or a column, a relationship has a `phpName`.
            By default, Propel uses the `phpName` of the foreign table as the `phpName` of the relation.

            The `refPhpName` defines the name of the relation as seen from the foreign table.
        -->
        <foreign-key foreignTable="publisher" phpName="Publisher" refPhpName="Book">
            <reference local="publisher_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="author">
            <reference local="author_id" foreign="id"/>
        </foreign-key>
    </entity>';

        $this->assertContains($stubContent, file_get_contents($this->tempDir . '/schema.xml'), 'schema.xml file contains some stub content.');
    }

    public function testExecuteConnectionFailure()
    {
        //Create the array of answers
        $answers['driver'] = $driver = $this->getDriver();

        //First, we create answers for wrong connection
        if ('sqlite' == $driver) {
            $answers['wrong_db_dir'] = 'fake_dir';
        } else {
            $answers['wrong_hostname'] = 'localhost';
            $answers['wrong_port'] = null;
            $answers['wrong_dbname'] = 'fake_db';
        }
        $answers['wrong_user'] = 'fake_user';
        $answers['wrong_pwd'] = 'fake_pwd';
        $answers['wrong_charset'] = null; //charset

        //Second, answers for correct connection
        if ('sqlite' == $driver) {
            $answers['db_dir'] = $this->tempDir;
        } else {
            $answers['hostname'] = getenv('DB_HOSTNAME') ?: 'localhost';
            $answers['port'] = null;
            $answers['dbname'] = getenv('DB_NAME') ?: 'test';
        }
        $answers['user'] = getenv('DB_USER') ?: 'root';
        $answers['pwd'] = getenv('DB_PW') ?: '';
        $answers['charset'] = null; //charset
        $answers['database'] = false; //existing database
        $answers[] = $this->tempDir; //schema dir
        $answers[] = $this->tempDir; //php dir
        $answers[] = 'myNamespace'; //namespace
        $answers[] = 'xml'; //config file format
        $answers[] = 'yes'; //confirm every answer

        $app = new Application();
        $app->add($this->command);

        $tester = new CommandTester($app->find('init'));
        $tester->setInputs($answers);
        $tester->execute(['command' => $this->command->getName()]);
        $output = $tester->getDisplay();
        $this->assertContains('[ERROR] Unable to connect to the specific sql server: ', $output);
        $this->assertContains('Make sure the specified credentials are correct and try it again.', $output);
    }

    public function testExecuteFormatError()
    {
        //Create the array of answers
        $answers['driver'] = $driver = $this->getDriver();
        if ('sqlite' == $driver) {
            $answers['db_dir'] = $this->tempDir;
        } else {
            $answers['hostname'] = getenv('DB_HOSTNAME') ?: 'localhost';
            $answers['port'] = null;
            $answers['dbname'] = getenv('DB_NAME') ?: 'test';
        }
        $answers['user'] = getenv('DB_USER') ?: 'root';
        $answers['pwd'] = getenv('DB_PW') ?: '';
        $answers['charset'] = null; //charset
        $answers['database'] = false; //existing database
        $answers[] = $this->tempDir; //schema dir
        $answers[] = $this->tempDir; //php dir
        $answers[] = 'myNamespace'; //namespace

        //wrong format
        $answers['wrong_format'] = 'foo';

        //correct format
        $answers['format'] = 'yml';
        $answers[] = 'yes'; //confirm every answer

        $app = new Application();
        $app->add($this->command);

        $tester = new CommandTester($app->find('init'));
        $tester->setInputs($answers);
        $tester->execute(['command' => $this->command->getName()]);
        $output = $tester->getDisplay();

        $this->assertContains('The specified format "foo" is invalid. Use one of php, ini, yml, xml, json', $output);
    }

    public function testExecuteWithReverse()
    {
        //Create the array of answers
        $answers['driver'] = $driver = $this->getDriver();
        if ('sqlite' == $driver) {
            $answers['db_dir'] = __DIR__ . '/../../../test.sq3';
        } else {
            $answers['hostname'] = getenv('DB_HOSTNAME') ?: 'localhost';
            $answers['port'] = null;
            $answers['dbname'] = 'test';
        }
        $answers['user'] = getenv('DB_USER') ?: 'root';
        $answers['pwd'] = getenv('DB_PW') ?: '';
        $answers['charset'] = null;
        $answers['database'] = 'yes'; //existing database
        $answers['schema_dir'] = $this->tempDir;
        $answers['php_dir'] = $this->tempDir;
        $answers['namespace'] = 'myNamespace';
        $answers['format'] = 'xml';
        $answers['confirm'] = 'yes'; //confirm every answer

        $app = new Application();
        $app->add(new DatabaseReverseCommand());
        $app->add($this->command);

        $tester = new CommandTester($app->find('init'));
        $tester->setInputs($answers);
        $tester->execute(['command' => $this->command->getName()]);
        $output = $tester->getDisplay();

        $this->assertContains('Schema reverse engineering finished.', $output, 'Reverse command successfully executed.');
    }

    private function cleanupTempDir()
    {
        if ($this->fileSystem->exists($this->tempDir)) {
            $this->fileSystem->remove($this->tempDir);
        }

        $this->fileSystem->mkdir($this->tempDir);
    }
}
