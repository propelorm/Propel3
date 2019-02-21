<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Schema\Dumper;

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Schema\Dumper\XmlDumper;
use Propel\Tests\TestCase;

class XmlDumperTest extends TestCase
{
    /**
     * The XmlDumper instance.
     *
     * @var XmlDumper
     */
    private $dumper;

    public function testDumpDatabaseSchema()
    {
        $database = include realpath(__DIR__.'/../../../Resources/blog-database.php');

        $this->assertSame($this->getExpectedXml('blog-database.xml'), $this->dumper->dump($database));
    }

    public function testDumpMyISAMSchema()
    {
        $platform = new MysqlPlatform();
        $schema = include realpath(__DIR__.'/../../../Resources/blog-schema.php');
        $platform->doFinalInitialization($schema);

        $this->assertSame($this->getExpectedXml('blog-schema.xml'), $this->dumper->dumpSchema($schema));
    }

    protected function getExpectedXml($filename)
    {
        return trim(file_get_contents(realpath(__DIR__.'/../../../Resources/'.$filename)));
    }

    protected function setUp()
    {
        $this->dumper = new XmlDumper();
    }

    protected function tearDown()
    {
        $this->dumper = null;
    }
}
