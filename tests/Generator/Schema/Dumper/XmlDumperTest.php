<?php declare(strict_types=1);
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
    private XmlDumper $dumper;

    protected function setUp(): void
    {
        $this->dumper = new XmlDumper();
    }

    public function testDumpDatabaseSchema(): void
    {
        $database = include __DIR__ . '/../../../Resources/blog-database.php';

        $this->assertSame($this->getExpectedXml('blog-database.xml'), $this->dumper->dump($database));
    }

    public function testDumpMyISAMSchema(): void
    {
        $platform = new MysqlPlatform();
        $schema = include __DIR__ . '/../../../Resources/blog-schema.php';
        $platform->doFinalInitialization($schema);

        $this->assertSame($this->getExpectedXml('blog-schema.xml'), $this->dumper->dumpSchema($schema));
    }

    protected function getExpectedXml(string $filename): string
    {
        return trim(file_get_contents(__DIR__.'/../../../Resources/'.$filename));
    }
}
