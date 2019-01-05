<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Schema;

use org\bovigo\vfs\vfsStream;
use Propel\Generator\Schema\SchemaReader;

class SchemaReaderTest extends ReaderTestCase
{
    public function testBookstore()
    {
        $reader = new SchemaReader();
        
        $schema = $reader->parse(__DIR__ . '/../../../../Fixtures/bookstore/schema.xml');
        
        $this->assertEquals(34, count($schema->getDatabase()->getEntities()), 'Correct number of entities');
        $this->assertEquals(1, count($schema->getDatabases()), 'Only one database');
        $this->assertEquals('bookstore', $schema->getDatabase()->getName());

        $entity = $schema->getDatabase()->getEntityByName('Book');
        $this->assertEquals(2, count($entity->getRelations()), 'Book entity has 2 relations');

        $entity = $schema->getDatabase()->getEntityByName('Author');
        $this->assertEquals(0, count($entity->getRelations()), 'Author entity has no relation');

        $entity = $schema->getDatabase()->getEntityByName('BookListRel');
        $this->assertTrue($entity->isCrossRef());
        $this->assertEquals(2, count($entity->getRelations()), 'Cross entities have 2 relations');
    }

    public function testExternalSchema()
    {
        $this->addExternalSchemas();

        $reader = new SchemaReader();
        $schema = $reader->parse(vfsStream::url('root/book.schema.xml'));

        $this->assertEquals(3, count($schema->getDatabase()->getEntities()), 'Correct number of entities');
        $this->assertEquals(1, count($schema->getDatabases()), 'Only one database');
        $this->assertEquals('bookstore', $schema->getDatabase()->getName());

        $entity = $schema->getDatabase()->getEntityByName('Book');
        $this->assertEquals(2, count($entity->getRelations()), 'Book entity has 2 relations');

        $entity = $schema->getDatabase()->getEntityByName('Author');
        $this->assertEquals(0, count($entity->getRelations()), 'Author entity has no relation');

        $entity = $schema->getDatabase()->getEntityByName('Publisher');
        $this->assertEquals(0, count($entity->getRelations()), 'Publisher entity has no relation');
    }

    public function testBehaviors()
    {
        $reader = new SchemaReader();
        $schema = $reader->parse(__DIR__ . '/../../../../Fixtures/bookstore/behavior-auto-add-pk-schema.xml');

        $this->assertEquals(3, count($schema->getDatabase()->getEntities()), 'Correct number of entities');
        $this->assertEquals(1, count($schema->getDatabases()), 'Only one database');
        $this->assertEquals('bookstore-behavior', $schema->getDatabase()->getName());

        $entity = $schema->getDatabase()->getEntityByName('Entity6');
        $this->assertEquals(0, count($entity->getRelations()), 'Entity6 entity has 0 relations');
        $this->assertTrue($entity->hasBehavior('auto_add_pk'));
    }
}
