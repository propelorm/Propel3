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
use phootwork\lang\Text;
use Propel\Generator\Model\Relation;

/**
 * Unit test suite for the Relation model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class RelationTest extends ModelTestCase
{
    public function testCreateNewRelation(): void
    {
        $fk = new Relation('book_author');

        $this->assertEquals('book_author', $fk->getName());
        $this->assertFalse($fk->hasOnUpdate());
        $this->assertFalse($fk->hasOnDelete());
        $this->assertFalse($fk->isComposite());
        $this->assertFalse($fk->isSkipSql());
    }

    public function testRelationIsForeignPrimaryKey(): void
    {
        $database     = $this->getDatabaseMock('bookstore');
        $platform     = $this->getPlatformMock();
        $foreignEntity = $this->getEntityMock('authors');

        $localEntity   = $this->getEntityMock('books', [
            'platform' => $platform,
            'database' => $database
        ]);

        $idField     = $this->getFieldMock('id');
        $authorIdField = $this->getFieldMock('author_id');

        $database
            ->expects($this->any())
            ->method('getEntityByName')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $foreignEntity
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue([$idField]))
        ;

        $foreignEntity
            ->expects($this->any())
            ->method('getFieldByName')
            ->with($this->equalTo('id'))
            ->will($this->returnValue($idField))
        ;

        $localEntity
            ->expects($this->any())
            ->method('getFieldByName')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($authorIdField))
        ;

        $fk = new Relation();
        $fk->setEntity($localEntity);
        $fk->setForeignEntityName('authors');
        $fk->addReference('author_id', 'id');

        $fkMapping = $fk->getFieldObjectsMapping();

        $this->assertTrue($fk->isForeignPrimaryKey());
        $this->assertCount(1, $fk->getForeignFieldObjects());
        $this->assertSame($authorIdField, $fkMapping[0]['local']);
        $this->assertSame($idField, $fkMapping[0]['foreign']);
        $this->assertSame($idField, $fk->getForeignField(0));
    }

    public function testRelationDoesNotUseRequiredFields(): void
    {
        $column = $this->getFieldMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(false))
        ;

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getFieldByName')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isLocalFieldsRequired());
    }

    public function testRelationUsesRequiredFields(): void
    {
        $column = $this->getFieldMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(true))
        ;

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getFieldByName')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalFieldsRequired());
    }

    public function testCantGetInverseRelation(): void
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(false);
        $foreignEntity = $this->getEntityMock('authors');

        $localEntity = $this->getEntityMock('books', [
            'platform' => $platform,
            'database' => $database
        ]);

        $database
            ->expects($this->any())
            ->method('getEntityByName')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $inversedFk = new Relation();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setEntity($localEntity);

        $foreignEntity
            ->expects($this->any())
            ->method('getRelations')
            ->will($this->returnValue(new Set([])))
        ;

        $fk = new Relation();
        $fk->setEntity($localEntity);
        $fk->addReference('author_id', 'id');
        $fk->setForeignEntityName('authors');

        $this->assertEquals('authors', $fk->getForeignEntityName());
        $this->assertNull($fk->getInverseFK());
        $this->assertFalse($fk->isMatchedByInverseFK());
    }

    public function testGetInverseRelation(): void
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(true);
        $foreignEntity = $this->getEntityMock('authors');

        $localEntity = $this->getEntityMock('books', [
            'platform' => $platform,
            'database' => $database
        ]);

        $database
            ->expects($this->any())
            ->method('getEntityByName')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $inversedFk = new Relation();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setEntity($localEntity);

        $foreignEntity
            ->expects($this->any())
            ->method('getRelations')
            ->will($this->returnValue(new Set([$inversedFk])))
        ;

        $fk = new Relation();
        $fk->setEntity($localEntity);
        $fk->addReference('author_id', 'id');
        $fk->setForeignEntityName('authors');

        $this->assertEquals('authors', $fk->getForeignEntityName());
        $this->assertInstanceOf('Propel\Generator\Model\Entity', $fk->getForeignEntity());
        $this->assertSame($inversedFk, $fk->getInverseFK());
        $this->assertTrue($fk->isMatchedByInverseFK());
    }

    public function testGetLocalField(): void
    {
        $column = $this->getFieldMock('id');

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->any())
            ->method('getFieldByName')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('author_id', 'id');

        $this->assertCount(1, $fk->getLocalFieldObjects());
        $this->assertInstanceOf('Propel\Generator\Model\Field', $fk->getLocalField(0));
    }

    public function testRelationIsNotLocalPrimaryKey(): void
    {
        $pks = [$this->getFieldMock('id')];

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('book_id', 'id');

        $this->assertFalse($fk->isLocalPrimaryKey());
    }

    public function testRelationIsLocalPrimaryKey(): void
    {
        $pks = [
            $this->getFieldMock('book_id'),
            $this->getFieldMock('author_id'),
        ];

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalPrimaryKey());
    }

    public function testGetOtherRelations(): void
    {
        $fk = new Relation();

        $fks[] = new Relation();
        $fks[] = $fk;
        $fks[] = new Relation();

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getRelations')
            ->will($this->returnValue(new Set($fks)))
        ;

        $fk->setEntity($table);

        $this->assertCount(2, $fk->getOtherFks());
    }

    public function testClearReferences(): void
    {
        $fk = new Relation();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');
        $fk->clearReferences();

        $this->assertCount(0, $fk->getLocalFields());
        $this->assertCount(0, $fk->getForeignFields());
    }

    public function testAddMultipleReferences(): void
    {
        $fk = new Relation();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isComposite());
        $this->assertCount(2, $fk->getLocalFields());
        $this->assertCount(2, $fk->getForeignFields());

        $this->assertEquals('book_id', $fk->getLocalFields()->get(0));
        $this->assertEquals('id', $fk->getForeignFields()->get(0));
        $this->assertEquals('id', $fk->getMappedForeignField('book_id'));

        $this->assertEquals('author_id', $fk->getLocalFields()->get(1));
        $this->assertEquals('id', $fk->getForeignFields()->get(1));
        $this->assertEquals('id', $fk->getMappedForeignField('author_id'));
    }

    public function testAddSingleStringReference(): void
    {
        $fk = new Relation();
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalFields());
        $this->assertCount(1, $fk->getForeignFields());

        $this->assertEquals('author_id', $fk->getMappedLocalField('id'));
    }

    public function testAddSingleArrayReference(): void
    {
        $reference = ['local' => 'author_id', 'foreign' => 'id'];

        $fk = new Relation();
        $fk->addReference($reference);

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalFields());
        $this->assertCount(1, $fk->getForeignFields());

        $this->assertSame($reference['local'], $fk->getMappedLocalField($reference['foreign']));
    }

    public function testAddSingleFieldReference(): void
    {
        $fk = new Relation();
        $fk->addReference(
            $this->getFieldMock('author_id'),
            $this->getFieldMock('id')
        );

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalFields());
        $this->assertCount(1, $fk->getForeignFields());

        $this->assertEquals('author_id', $fk->getMappedLocalField('id'));
    }

    public function testSetEntity(): void
    {
        $table = $this->getEntityMock('book');
        $table
            ->expects($this->once())
            ->method('getSchemaName')
            ->will($this->returnValue(new Text('books')))
        ;

        $fk = new Relation();
        $fk->setEntity($table);

        $this->assertInstanceOf('Propel\Generator\Model\Entity', $fk->getEntity());
        $this->assertEquals('books', $fk->getSchemaName());
        $this->assertEquals('book', $fk->getEntityName());
    }

    public function testSetDefaultJoin(): void
    {
        $fk = new Relation();
        $fk->setDefaultJoin('INNER');

        $this->assertSame('INNER', $fk->getDefaultJoin());
    }

    public function testSetNames(): void
    {
        $fk = new Relation();
        $fk->setName('book_author');
        $fk->setField('author');
        $fk->setRefField('books');

        $this->assertEquals('book_author', $fk->getName());
        $this->assertSame('author', $fk->getField());
        $this->assertSame('books', $fk->getRefField());
    }

    public function testSkipSql(): void
    {
        $fk = new Relation();
        $fk->setSkipSql(true);

        $this->assertTrue($fk->isSkipSql());
    }

    public function testGetOnActionBehaviors(): void
    {
        $fk = new Relation();
        $fk->setOnUpdate('SETNULL');
        $fk->setOnDelete('CASCADE');

        $this->assertSame('SET NULL', $fk->getOnUpdate());
        $this->assertTrue($fk->hasOnUpdate());

        $this->assertSame('CASCADE', $fk->getOnDelete());
        $this->assertTrue($fk->hasOnDelete());
    }

    /**
     * @dataProvider provideOnActionBehaviors
     *
     */
    public function testNormalizeRelation($behavior, $normalized): void
    {
        $fk = new Relation();

        $this->assertSame($normalized, $fk->normalizeFKey($behavior));
    }

    public function provideOnActionBehaviors(): array
    {
        return [
            [null, ''],
            ['none', ''],
            ['NONE', ''],
            ['setnull', 'SET NULL'],
            ['SETNULL', 'SET NULL'],
            ['cascade', 'CASCADE'],
            ['CASCADE', 'CASCADE'],
        ];
    }
}
