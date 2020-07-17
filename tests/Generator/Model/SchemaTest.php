<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use phootwork\collection\Set;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;

/**
 * Unit test suite for the Schema model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class SchemaTest extends ModelTestCase
{
    public function testCreateNewSchema(): void
    {
        $platform = $this->getPlatformMock();

        $schema = new Schema($platform);
        $this->assertSame($platform, $schema->getPlatform());
        $this->assertCount(0, $schema->getDatabases());
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    public function testJoinMultipleSchemasWithSameEntityTwice(): void
    {
        $database1 = new Database('bookstore');
        $database1->addEntity($this->getEntityMock('books'));

        $database2 = new Database('bookstore');
        $database2->addEntity($this->getEntityMock('books'));

        $subSchema1 = new Schema();
        $subSchema1->addDatabase($database1);

        $schema = new Schema();
        $schema->addDatabase($database2);

        $this->expectException('Propel\Generator\Exception\EngineException');

        $schema->joinSchemas([$subSchema1]);
    }

    public function testJoinMultipleSchemasWithSameDatabase(): void
    {
        $behavior = $this->getBehaviorMock('sluggable');

        $tables[] = $this->getEntityMock('books');
        $tables[] = $this->getEntityMock('authors');

        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->any())
            ->method('countEntities')
            ->will($this->returnValue(count($tables)))
        ;
        $database
            ->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue(new Set($tables)))
        ;
        $database
            ->expects($this->any())
            ->method('getBehaviors')
            ->will($this->returnValue([$behavior]))
        ;

        $subSchema1 = new Schema($this->getPlatformMock());
        $subSchema1->addDatabase($database);

        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase($database);

        $schema->joinSchemas([$subSchema1]);

        $this->assertCount(1, $schema->getDatabases());
        $this->assertSame(2, $schema->countEntities());
    }

    public function testJoinMultipleSchemasWithoutEntities(): void
    {
        $subSchema1 = new Schema($this->getPlatformMock());
        $subSchema1->addDatabase(new Database('bookstore'));
        $subSchema1->addDatabase(new Database('shoestore'));

        $subSchema2 = new Schema($this->getPlatformMock());
        $subSchema2->addDatabase(new Database('surfstore'));

        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase(new Database('skatestore'));

        $schema->joinSchemas([$subSchema1, $subSchema2]);

        $this->assertCount(4, $schema->getDatabases());
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertTrue($schema->hasDatabase('shoestore'));
        $this->assertTrue($schema->hasDatabase('surfstore'));
        $this->assertTrue($schema->hasDatabase('skatestore'));
    }

    public function testGetFirstDatabase(): void
    {
        $db = new Database('bookstore');
        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase($db);

        $this->assertSame($db, $schema->getDatabase());
    }

    public function testDatabases(): void
    {
        $db1 = new Database('bookstore');
        $db2 = new Database('shoestore');
        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase($db1);
        $schema->addDatabase($db2);

        $this->assertSame($db2, $schema->getDatabase('shoestore'));
        $this->assertEquals(2, $schema->getDatabaseSize());
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertTrue($schema->hasDatabase('shoestore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertTrue($schema->hasMultipleDatabases());
    }

    public function testGetNoDatabase(): void
    {
        $schema = new Schema($this->getPlatformMock());

        $this->assertNull($schema->getDatabase('shoestore'));
    }

    public function testExternalSchema(): void
    {
        $p1 = $this->getPlatformMock();
        $p2 = $this->getPlatformMock();
        $root = new Schema($p1);
        $child = new Schema();
        $child->setSchema($root);

        $this->assertTrue($child->isExternalSchema());
        $this->assertSame($root, $child->getRootSchema());
        $this->assertEquals(1, $root->getExternalSchemaSize());
        $this->assertSame($p1, $child->getPlatform());

        $child->setPlatform($p2);
        $this->assertSame($p2, $child->getPlatform());
    }

    public function testAddArrayDatabase(): void
    {
        $config = $this
            ->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $config
            ->expects($this->any())
            ->method('createPlatformForDatabase')
            ->with($this->equalTo(null), $this->equalTo('bookstore'))
            ->will($this->returnValue($this->getPlatformMock()))
        ;

        $schema = new Schema($this->getPlatformMock());
        $schema->setGeneratorConfig($config);
        $schema->addDatabase(new Database('bookstore'));

        $this->assertCount(1, $schema->getDatabases());
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    public function testAddArrayDatabaseWithDefaultPlatform(): void
    {
        $schema = new Schema($this->getPlatformMock());
        $schema->addDatabase(new Database('bookstore'));

        $this->assertCount(1, $schema->getDatabases());
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertFalse($schema->hasMultipleDatabases());
    }

    public function testAddDatabase(): void
    {
        $database1 = $this->getDatabaseMock('bookstore');
        $database2 = $this->getDatabaseMock('shoestore');
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = new Schema($this->getPlatformMock());
        $schema->setGeneratorConfig($config);
        $schema->addDatabase($database1);
        $schema->addDatabase($database2);

        $this->assertCount(2, $schema->getDatabases());
        $this->assertTrue($schema->hasDatabase('bookstore'));
        $this->assertTrue($schema->hasDatabase('shoestore'));
        $this->assertFalse($schema->hasDatabase('foostore'));
        $this->assertTrue($schema->hasMultipleDatabases());
    }

    public function testSetName(): void
    {
        $schema = new Schema();
        $schema->setName('bookstore-schema');

        $this->assertEquals('bookstore-schema', $schema->getName());
    }

    public function testSetGeneratorConfig(): void
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $root = new Schema();
        $root->setGeneratorConfig($config);

        $this->assertSame($config, $root->getGeneratorConfig());

        $child = new Schema();
        $root->addExternalSchema($child);

        $this->assertSame($config, $child->getGeneratorConfig());
    }
}
