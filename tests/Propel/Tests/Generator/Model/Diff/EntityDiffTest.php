<?php

namespace Propel\Tests\Generator\Model\Diff;

use phootwork\collection\Map;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\SqlDefaultPlatform;
use Propel\Tests\TestCase;

class EntityDiffTest extends TestCase
{
    public function testDefaultObjectState()
    {
        $fromEntity = new Entity('article');
        $toEntity   = new Entity('article');

        $diff = $this->createEntityDiff($fromEntity, $toEntity);
        
        $this->assertSame($fromEntity, $diff->getFromEntity());
        $this->assertSame($toEntity, $diff->getToEntity());
        $this->assertFalse($diff->hasAddedFields());
        $this->assertFalse($diff->hasAddedFks());
        $this->assertFalse($diff->hasAddedIndices());
        $this->assertFalse($diff->hasAddedPkFields());
        $this->assertFalse($diff->hasModifiedFields());
        $this->assertFalse($diff->hasModifiedFks());
        $this->assertFalse($diff->hasModifiedIndices());
        $this->assertFalse($diff->hasModifiedPk());
        $this->assertFalse($diff->hasRemovedFields());
        $this->assertFalse($diff->hasRemovedFks());
        $this->assertFalse($diff->hasRemovedIndices());
        $this->assertFalse($diff->hasRemovedPkFields());
        $this->assertFalse($diff->hasRenamedFields());
        $this->assertFalse($diff->hasRenamedPkFields());
    }

    public function testSetAddedFields()
    {
        $column = new Field('is_published', 'boolean');

        $diff = $this->createEntityDiff();
        $diff->setAddedFields(new Map(['is_published' => $column]));

        $this->assertCount(1, $diff->getAddedFields());
        $this->assertSame($column, $diff->getAddedFields()->get('is_published'));
        $this->assertTrue($diff->hasAddedFields());
    }

    public function testSetRemovedFields()
    {
        $column = new Field('is_active');

        $diff = $this->createEntityDiff();
        $diff->setRemovedFields(new Map(['is_active' => $column]));

        $this->assertEquals(1, $diff->getRemovedFields()->size());
        $this->assertSame($column, $diff->getRemovedFields()->get('is_active'));
        $this->assertTrue($diff->hasRemovedFields());
    }

    public function testSetModifiedFields()
    {
        $columnDiff = new FieldDiff();

        $diff = $this->createEntityDiff();
        $diff->setModifiedFields(new Map(['title' => $columnDiff]));

        $this->assertEquals(1, $diff->getModifiedFields()->size());
        $this->assertTrue($diff->hasModifiedFields());
    }

    public function testAddRenamedField()
    {
        $fromField = new Field('is_published', 'boolean');
        $toField   = new Field('is_active', 'boolean');

        $diff = $this->createEntityDiff();
        $diff->setRenamedFields(new Map(['is_published' => [$fromField, $toField]]));

        $this->assertEquals(1, $diff->getRenamedFields()->size());
        $this->assertTrue($diff->hasRenamedFields());
    }

    public function testSetAddedPkFields()
    {
        $column = new Field('id', 'integer', 7);
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setAddedPkFields(new Map(['id' => [$column]]));

        $this->assertEquals(1, $diff->getAddedPkFields()->size());
        $this->assertTrue($diff->hasAddedPkFields());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testSetRemovedPkFields()
    {
        $column = new Field('id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setRemovedPkFields(new Map(['id' => [$column]]));

        $this->assertCount(1, $diff->getRemovedPkFields());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testSetRenamedPkFields()
    {
        $diff = $this->createEntityDiff();
        $diff->setRenamedPkFields(new Map(['id' => [new Field('id', 'integer'), new Field('post_id', 'integer')]]));

        $this->assertCount(1, $diff->getRenamedPkFields());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testSetAddedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->setAddedIndices(new Map(['username_unique_idx' => $index]));

        $this->assertCount(1, $diff->getAddedIndices());
        $this->assertTrue($diff->hasAddedIndices());
    }

    public function testSetRemovedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->setRemovedIndices(new Map(['username_unique_idx' => $index]));

        $this->assertCount(1, $diff->getRemovedIndices());
        $this->assertTrue($diff->hasRemovedIndices());
    }

    public function testSetModifiedIndices()
    {
        $table = new Entity('users');
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('username_unique_idx');
        $fromIndex->setEntity($table);
        $fromIndex->addFields([new Field('username')]);

        $toIndex = new Index('username_unique_idx');
        $toIndex->setEntity($table);
        $toIndex->addFields([new Field('client_id'), new Field('username')]);

        $diff = $this->createEntityDiff();
        $diff->setModifiedIndices(new Map(['username_unique_idx' => [$fromIndex, $toIndex]]));

        $this->assertEquals(1, $diff->getModifiedIndices()->size());
        $this->assertTrue($diff->hasModifiedIndices());
    }

    public function testSetAddedFks()
    {
        $fk = new Relation('fk_blog_author');

        $diff = $this->createEntityDiff();
        $diff->setAddedFks(new Map(['fk_blog_author' => $fk]));

        $this->assertEquals(1, $diff->getAddedFks()->size());
        $this->assertTrue($diff->hasAddedFks());
    }

    public function testSetRemovedFk()
    {
        $diff = $this->createEntityDiff();
        $diff->setRemovedFks(new Map(['fk_blog_post_author' => new Relation('fk_blog_post_author')]));

        $this->assertEquals(1, $diff->getRemovedFks()->size());
        $this->assertTrue($diff->hasRemovedFks());
    }

    public function testSetModifiedFks()
    {
        $diff = $this->createEntityDiff();
        $diff->setModifiedFks(new Map(['blog_post_author' => [new Relation('blog_post_author'), new Relation('blog_post_has_author')]]));

        $this->assertEquals(1, $diff->getModifiedFks()->size());
        $this->assertTrue($diff->hasModifiedFks());
    }

    public function testGetSimpleReverseDiff()
    {
        $tableA = new Entity('users');
        $tableB = new Entity('users');

        $diff = $this->createEntityDiff($tableA, $tableB);
        $reverseDiff = $diff->getReverseDiff();

        $this->assertInstanceOf('Propel\Generator\Model\Diff\EntityDiff', $reverseDiff);
        $this->assertSame($tableA, $reverseDiff->getToEntity());
        $this->assertSame($tableB, $reverseDiff->getFromEntity());
    }

    public function testReverseDiffHasModifiedFields()
    {
        $c1 = new Field('title', 'varchar', 50);
        $c2 = new Field('title', 'varchar', 100);

        $columnDiff = new FieldDiff($c1, $c2);
        $reverseFieldDiff = $columnDiff->getReverseDiff();

        $diff = $this->createEntityDiff();
        $diff->getModifiedFields()->set('title', $columnDiff);
        
        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedFields());
        $this->assertEquals(['title' => $reverseFieldDiff], $reverseDiff->getModifiedFields()->toArray());
    }

    public function testReverseDiffHasRemovedFields()
    {
        $column = new Field('slug', 'varchar', 100);

        $diff = $this->createEntityDiff();
        $diff->getAddedFields()->set('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame(['slug' => $column], $reverseDiff->getRemovedFields()->toArray());
        $this->assertSame($column, $reverseDiff->getRemovedFields()->get('slug'));
    }

    public function testReverseDiffHasAddedFields()
    {
        $column = new Field('slug', 'varchar', 100);

        $diff = $this->createEntityDiff();
        $diff->getRemovedFields()->set('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame(['slug' => $column], $reverseDiff->getAddedFields()->toArray());
        $this->assertSame($column, $reverseDiff->getAddedFields()->get('slug'));
    }

    public function testReverseDiffHasRenamedFields()
    {
        $columnA = new Field('login', 'varchar', 15);
        $columnB = new Field('username', 'varchar', 15);

        $diff = $this->createEntityDiff();
        $diff->getRenamedFields()->set('login', [$columnA, $columnB]);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([$columnB, $columnA], $reverseDiff->getRenamedFields()->get('username'));
    }

    public function testReverseDiffHasAddedPkFields()
    {
        $column = new Field('client_id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->getRemovedPkFields()->set('client_id', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertEquals(1, $reverseDiff->getAddedPkFields()->size());
        $this->assertTrue($reverseDiff->hasAddedPkFields());
    }

    public function testReverseDiffHasRemovedPkFields()
    {
        $column = new Field('client_id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->getAddedPkFields()->set('client_id', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertEquals(1, $reverseDiff->getRemovedPkFields()->size());
        $this->assertTrue($reverseDiff->hasRemovedPkFields());
    }

    public function testReverseDiffHasRenamedPkField()
    {
        $fromField = new Field('post_id', 'integer');
        $fromField->setPrimaryKey();

        $toField = new Field('id', 'integer');
        $toField->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->getRenamedPkFields()->set('post_id', [$fromField, $toField]);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRenamedPkFields());
        $this->assertEquals([$toField, $fromField], $reverseDiff->getRenamedPkFields()->get('id'));
    }

    public function testReverseDiffHasAddedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->getRemovedIndices()->set('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedIndices());
        $this->assertCount(1, $reverseDiff->getAddedIndices());
    }

    public function testReverseDiffHasRemovedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->getAddedIndices()->set('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedIndices());
        $this->assertCount(1, $reverseDiff->getRemovedIndices());
    }

    public function testReverseDiffHasModifiedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('i1');
        $fromIndex->setEntity($table);

        $toIndex = new Index('i1');
        $toIndex->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->getModifiedIndices()->set('i1', [$fromIndex, $toIndex]);

        $reverseDiff = $diff->getReverseDiff();

        $this->assertTrue($reverseDiff->hasModifiedIndices());
        $this->assertSame(['i1' => [$toIndex, $fromIndex]], $reverseDiff->getModifiedIndices()->toArray());
    }

    public function testReverseDiffHasRemovedFks()
    {
        $diff = $this->createEntityDiff();
        $diff->getAddedFks()->set('fk_post_author', new Relation('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedFks());
        $this->assertCount(1, $reverseDiff->getRemovedFks());
    }

    public function testReverseDiffHasAddedFks()
    {
        $diff = $this->createEntityDiff();
        $diff->getRemovedFks()->set('fk_post_author', new Relation('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedFks());
        $this->assertCount(1, $reverseDiff->getAddedFks());
    }

    public function testReverseDiffHasModifiedFks()
    {
        $fromFk = new Relation('fk_1');
        $toFk = new Relation('fk_1');

        $diff = $this->createEntityDiff();
        $diff->getModifiedFks()->set('fk_1', [$fromFk, $toFk]);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedFks());
        $this->assertSame(['fk_1' => [$toFk, $fromFk]], $reverseDiff->getModifiedFks()->toArray());
    }
    
    private function createEntityDiff(Entity $fromEntity = null, Entity $toEntity = null)
    {
        if (null === $fromEntity) {
            $fromEntity = new Entity('users');
        }

        if (null === $toEntity) {
            $toEntity = new Entity('users');
        }

        return new EntityDiff($fromEntity, $toEntity);
    }

    public function testToString()
    {
        $tableA = new Entity('A');
        $tableB = new Entity('B');

        $diff = new EntityDiff($tableA, $tableB);
        $diff->getAddedFields()->set('id', new Field('id', 'integer'));
        $diff->getRemovedFields()->set('category_id', new Field('category_id', 'integer'));

        $colFoo = new Field('foo', 'integer');
        $colBar = new Field('bar', 'integer');
        $tableA->addField($colFoo);
        $tableA->addField($colBar);

        $diff->getRenamedFields()->set('foo', [$colFoo, $colBar]);
        $columnDiff = new FieldDiff($colFoo, $colBar);
        $diff->getModifiedFields()->set('foo', $columnDiff);

        $fk = new Relation('category');
        $fk->setEntity($tableA);
        $fk->setForeignEntityName('B');
        $fk->addReference('category_id', 'id');

        //Clone doesn't work by now
        $fkChanged = new Relation('category');
        $fkChanged->setEntity($tableA);
        $fkChanged->setForeignEntityName('B');
        $fkChanged->addReference('category_id', 'id');
        $fkChanged->setForeignEntityName('C');
        $fkChanged->addReference('bla', 'id2');
        $fkChanged->setOnDelete('cascade');
        $fkChanged->setOnUpdate('cascade');

        $diff->getAddedFks()->set('category', $fk);
        $diff->getModifiedFks()->set('category', [$fk, $fkChanged]);
        $diff->getRemovedFks()->set('category', $fk);

        $index = new Index('test_index');
        $index->setEntity($tableA);
        $index->addFields([$colFoo]);

        $indexChanged = clone $index;
        $indexChanged->addFields([$colBar]);

        $diff->getAddedIndices()->set('test_index', $index);
        $diff->getModifiedIndices()->set('test_index', [$index, $indexChanged]);
        $diff->getRemovedIndices()->set('test_index', $index);

        $string = (string) $diff;

        $expected = '  A:
    addedFields:
      - id
    removedFields:
      - category_id
    modifiedFields:
      A.FOO:
        modifiedProperties:
    renamedFields:
      foo: bar
    addedIndices:
      - test_index
    removedIndices:
      - test_index
    modifiedIndices:
      - test_index
    addedFks:
      - category
    removedFks:
      - category
    modifiedFks:
      category:
          localFields: from ["category_id"] to ["category_id","bla"]
          foreignFields: from ["id"] to ["id","id2"]
          onUpdate: from  to CASCADE
          onDelete: from  to CASCADE
';

        $this->assertEquals($expected, $string);
    }

    public function testMagicClone()
    {
        $diff = new EntityDiff(new Entity('A'), new Entity('B'));

        $clonedDiff = clone $diff;

        $this->assertNotSame($clonedDiff, $diff);
        $this->assertNotSame($clonedDiff->getFromEntity(), $diff->getFromEntity());
        $this->assertNotSame($clonedDiff->getToEntity(), $diff->getToEntity());
    }
}
