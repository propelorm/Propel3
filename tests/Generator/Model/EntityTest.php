<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\CrossRelation;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\NamingTool;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\SqlitePlatform;

/**
 * Unit test suite for Entity model class.
 *
 * @author Martin Poeschl <mpoeschl@marmot.at>
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class EntityTest extends ModelTestCase
{
    public function testCreateNewEntity()
    {
        $entity = new Entity('books');

        $this->assertSame('books', $entity->getName());
        $this->assertFalse($entity->isAllowPkInsert());
        $this->assertFalse($entity->isCrossRef());
        $this->assertFalse($entity->isReloadOnInsert());
        $this->assertFalse($entity->isReloadOnUpdate());
        $this->assertFalse($entity->isSkipSql());
        $this->assertFalse($entity->isReadOnly());
        $this->assertSame(0, $entity->getNumLazyLoadFields());
        $this->assertEmpty($entity->getChildrenNames());
        $this->assertFalse($entity->hasRelations());
    }

    /**
     * @dataProvider provideNamespaces
     *
     */
    public function testSetNamespace($namespace, $expected)
    {
        $entity = new Entity();
        $entity->setNamespace($namespace);

        $this->assertSame($expected, $entity->getNamespace());
    }

    public function provideNamespaces()
    {
        return [
            ['\Acme', '\Acme'],
            ['Acme', 'Acme'],
            ['Acme\\', 'Acme'],
            ['\Acme\Model', '\Acme\Model'],
            ['Acme\Model', 'Acme\Model'],
            ['Acme\Model\\', 'Acme\Model'],
        ];
    }

    public function testSetRepository()
    {
        $entity = new Entity('Book');
        $entity->setRepository('BookRepository');

        $this->assertEquals('BookRepository', $entity->getRepository());
    }

    public function testNames()
    {
        $entity = new Entity('Wurst\\Und\\Kaese');
        $this->assertEquals('Kaese', $entity->getName());
        $this->assertEquals('Wurst\\Und', $entity->getNamespace());


        $entity = new Entity();
        $this->assertEmpty($entity->getName());

        $entity->setName('Book');
        $this->assertEquals('Book', $entity->getName());
        $this->assertEquals('book', $entity->getTableName());

        $entity->setName('BookAuthor');
        $this->assertEquals('BookAuthor', $entity->getName());
        $this->assertEquals('book_author', $entity->getTableName());

        $entity->setTableName('book_has_author');
        $this->assertEquals('BookAuthor', $entity->getName());
        $this->assertEquals('book_has_author', $entity->getTableName());

        $entity->setScope('bookstore_');
        $this->assertEquals('bookstore_book_has_author', $entity->getScopedTableName());

        $entity->setNamespace('Bookstore');
        $this->assertEquals('Bookstore\\BookAuthor', $entity->getFullName());

        $entity = new Entity();
        $database = new Database();
        $database->setScope('bookings_');
        $database->setNamespace('Bookstore');
        $entity->setDatabase($database);

        $this->assertEquals('Bookstore', $entity->getNamespace());
        $this->assertEquals('bookings_', $entity->getScope());
    }

    public function testGetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();
        $database = $this->getDatabaseMock('foo');

        $database
            ->expects($this->once())
            ->method('getGeneratorConfig')
            ->will($this->returnValue($config))
        ;

        $entity = new Entity();
        $entity->setDatabase($database);

        $this->assertSame($config, $entity->getGeneratorConfig());
    }

    public function testApplyBehaviors()
    {
        $behavior = $this->getBehaviorMock('foo');
        $behavior
            ->expects($this->once())
            ->method('isEntityModified')
            ->will($this->returnValue(false))
        ;

        $behavior
            ->expects($this->once())
            ->method('getEntityModifier')
            ->will($this->returnValue($behavior))
        ;

        $behavior
            ->expects($this->once())
            ->method('modifyEntity')
        ;

        $behavior
            ->expects($this->once())
            ->method('setEntityModified')
            ->with($this->equalTo(true))
        ;

        $entity = new Entity();
        $entity->addBehavior($behavior);
        $entity->applyBehaviors();
    }

    public function testGetAdditionalBuilders()
    {
        $additionalBehaviors = [
            $this->getBehaviorMock('foo'),
            $this->getBehaviorMock('bar'),
            $this->getBehaviorMock('baz'),
        ];

        $behavior = $this->getBehaviorMock('mix', [
            'additional_builders' => $additionalBehaviors,
        ]);

        $entity = new Entity();
        $entity->addBehavior($behavior);

        $this->assertCount(3, $entity->getAdditionalBuilders());
        $this->assertTrue($entity->hasAdditionalBuilders());
    }

    public function testHasNoAdditionalBuilders()
    {
        $entity = new Entity();
        $entity->addBehavior($this->getBehaviorMock('foo'));

        $this->assertCount(0, $entity->getAdditionalBuilders());
        $this->assertFalse($entity->hasAdditionalBuilders());
    }

    public function testGetNameWithoutPlatform()
    {
        $entity = new Entity('books');

        $this->assertSame('books', $entity->getName());
    }

    /**
     * @dataProvider provideSchemaNames
     *
     */
    public function testGetNameWithPlatform($supportsSchemas, $schemaName, $expectedName)
    {
        $platform = $this->getPlatformMock($supportsSchemas);
        $platform
            ->expects($supportsSchemas ? $this->once() : $this->never())
            ->method('getSchemaDelimiter')
            ->will($this->returnValue('.'))
        ;

        $database = $this->getDatabaseMock($schemaName, [
            'platform' => $platform,
        ]);

        $schema = $this->getSchemaMock($schemaName);
        $database
            ->method('getSchema')
            ->will($this->returnValue($schema))
        ;

        $entity = new Entity('books');
        if ($supportsSchemas) {
            $entity->setSchemaName($schemaName);
        }
        $entity->setDatabase($database);
        $entity->getDatabase()->setSchema($schema);

        $this->assertSame($expectedName, $entity->getFullTableName());
    }

    public function provideSchemaNames()
    {
        return [
            [false, 'bookstore', 'books'],
            [false, null, 'books'],
            [true, 'bookstore', 'bookstore.books'],
        ];
    }

    public function testGetOverrideSchemaName()
    {
        $entity = new Entity();
        $entity->setDatabase($this->getDatabaseMock('bookstore'));
        $entity->setSchemaName('my_schema');

        $this->assertEquals('my_schema', $entity->guessSchemaName());
    }

    public function testSetDescription()
    {
        $entity = new Entity();

        $this->assertFalse($entity->hasDescription());

        $entity->setDescription('Some description');
        $this->assertNotNull($entity->getDescription());
        $this->assertSame('Some description', $entity->getDescription());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidStringFormat()
    {
        $entity = new Entity();
        $entity->setStringFormat('FOO');
    }

    public function testGetStringFormatFromDatabase()
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getStringFormat')
            ->will($this->returnValue('XML'))
        ;

        $entity = new Entity();
        $entity->setDatabase($database);

        $this->assertSame('XML', $entity->getStringFormat());
    }

    /**
     * @dataProvider provideStringFormats
     *
     */
    public function testGetStringFormat($format)
    {
        $entity = new Entity();
        $entity->setStringFormat($format);

        $this->assertSame($format, $entity->getStringFormat());
    }

    public function provideStringFormats()
    {
        return [
            ['XML'],
            ['YAML'],
            ['JSON'],
            ['CSV'],
        ];
    }

    public function testAddSameFieldTwice()
    {
        $entity = new Entity('books');
        $field = $this->getFieldMock('created_at', ['phpName' => 'CreatedAt']);

        $this->expectException('Propel\Generator\Exception\EngineException');

        $entity->addField($field);
        $entity->addField($field);
    }

    public function testGetChildrenNames()
    {
        $field = new Field('created_at');
        $field->setInheritanceType('single');

        $inherit = new Inheritance();
        $inherit->setKey('one');
        $field->addInheritance($inherit);

        $inherit1 = new Inheritance();
        $inherit1->setKey('two');
        $field->addInheritance($inherit1);

        $entity = new Entity('books');
        $entity->addField($field);

        $names = $entity->getChildrenNames();
        $this->assertCount(2, $names);

        $this->assertSame('Propel\Generator\Model\Inheritance', $names[0]);
        $this->assertSame('Propel\Generator\Model\Inheritance', $names[1]);
    }

    public function testCantGetChildrenNames()
    {
        $field = $this->getFieldMock('created_at', ['inheritance' => true]);

        $field
            ->expects($this->any())
            ->method('isEnumeratedClasses')
            ->will($this->returnValue(false))
        ;

        $entity = new Entity('books');
        $entity->addField($field);

        $this->assertEmpty($entity->getChildrenNames());
    }

    public function testAddInheritanceField()
    {
        $entity = new Entity('books');
        $field = $this->getFieldMock('created_at', ['inheritance' => true]);
        $entity->addField($field);
        $this->assertInstanceOf('Propel\Generator\Model\Field', $entity->getChildrenField());
        $this->assertTrue($entity->hasField($field));
        $this->assertTrue($entity->hasField($field));
        $this->assertCount(1, $entity->getFields());
        $this->assertSame(1, $entity->getNumFields());
        $this->assertTrue($entity->requiresTransactionInPostgres());
    }

    public function testHasBehaviors()
    {
        $behavior1 = $this->getBehaviorMock('Foo');
        $behavior2 = $this->getBehaviorMock('Bar');
        $behavior3 = $this->getBehaviorMock('Baz');

        $entity = new Entity();
        $entity->addBehavior($behavior1);
        $entity->addBehavior($behavior2);
        $entity->addBehavior($behavior3);

        $this->assertCount(3, $entity->getBehaviors());

        $this->assertTrue($entity->hasBehavior('Foo'));
        $this->assertTrue($entity->hasBehavior('Bar'));
        $this->assertTrue($entity->hasBehavior('Baz'));
        $this->assertFalse($entity->hasBehavior('Bab'));

        $this->assertSame($behavior1, $entity->getBehavior('Foo'));
        $this->assertSame($behavior2, $entity->getBehavior('Bar'));
        $this->assertSame($behavior3, $entity->getBehavior('Baz'));
    }

    public function testUnregisterBehavior()
    {
        $behavior = new Behavior();
        $behavior->setName('foo');
        $entity = new Entity();
        $entity->addBehavior($behavior);
        $this->assertTrue($entity->hasBehavior('foo'));
        $this->assertSame($entity, $behavior->getEntity());

        $entity->removeBehavior($behavior);
        $this->assertFalse($entity->hasBehavior('foo'));
        $this->assertNull($behavior->getEntity());
    }

    public function testAddField()
    {
        $entity = new Entity('books');
        $field = $this->getFieldMock('createdAt');
        $entity->addField($field);
        $this->assertNull($entity->getChildrenField());
        $this->assertTrue($entity->requiresTransactionInPostgres());
        $this->assertTrue($entity->hasField($field));
        $this->assertSame($field, $entity->getField('createdAt'));
        $this->assertCount(1, $entity->getFields());
        $this->assertSame(1, $entity->getNumFields());
    }

    /**
     * @expectedException \Propel\Generator\Exception\EngineException
     */
    public function testCantRemoveFieldWhichIsNotInEntity()
    {
        $field1 = $this->getFieldMock('title');

        $entity = new Entity('books');
        $entity->removeField($field1);
    }

    public function testRemoveFieldByName()
    {
        $field1 = $this->getFieldMock('id');
        $field2 = $this->getFieldMock('title');
        $field3 = $this->getFieldMock('isbn');

        $entity = new Entity('books');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);
        $entity->removeField('title');

        $this->assertCount(2, $entity->getFields());
        $this->assertTrue($entity->hasField('id'));
        $this->assertTrue($entity->hasField('isbn'));
        $this->assertFalse($entity->hasField('title'));
    }

    public function testRemoveField()
    {
        $field1 = $this->getFieldMock('id');
        $field2 = $this->getFieldMock('title');
        $field3 = $this->getFieldMock('isbn');

        $entity = new Entity('books');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);
        $entity->removeField($field2);

        $this->assertCount(2, $entity->getFields());
        $this->assertTrue($entity->hasField('id'));
        $this->assertTrue($entity->hasField('isbn'));
        $this->assertFalse($entity->hasField('title'));
    }

    public function testGetNumLazyLoadFields()
    {
        $field1 = $this->getFieldMock('created_at');
        $field2 = $this->getFieldMock('updated_at', ['lazy' => true]);

        $field3 = $this->getFieldMock('deleted_at', ['lazy' => true]);

        $entity = new Entity('books');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);

        $this->assertSame(2, $entity->getNumLazyLoadFields());
    }

    public function testHasEnumFields()
    {
        $field1 = $this->getFieldMock('created_at');
        $field2 = $this->getFieldMock('updated_at');

        $field1
            ->expects($this->any())
            ->method('isEnumType')
            ->will($this->returnValue(false))
        ;

        $field2
            ->expects($this->any())
            ->method('isEnumType')
            ->will($this->returnValue(true))
        ;

        $entity = new Entity('books');

        $entity->addField($field1);
        $this->assertFalse($entity->hasEnumFields());

        $entity->addField($field2);
        $this->assertTrue($entity->hasEnumFields());
    }

    public function testCantGetField()
    {
        $entity = new Entity('books');

        $this->assertFalse($entity->hasField('FOO', true));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCantGetFieldException()
    {
        $entity = new Entity('books');

        $this->assertNull($entity->getField('FOO'));
    }

    public function testSetAbstract()
    {
        $entity = new Entity();
        $this->assertFalse($entity->isAbstract());

        $entity->setAbstract(true);
        $this->assertTrue($entity->isAbstract());
    }

    public function testAddIndex()
    {
        $entity = new Entity();
        $index = new Index();
        $field = new Field();
        $field->setName('bla');
        $field->setEntity($entity);
        $index->addField($field);
        $entity->addIndex($index);

        $this->assertCount(1, $entity->getIndices());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddEmptyIndex()
    {
        $entity = new Entity();
        $entity->addIndex(new Index());

        $this->assertCount(1, $entity->getIndices());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddAlreadyCreatedIndex()
    {
        $index = $this->getIndexMock('idx_fake_entity');
        $entity = new Entity();
        $entity->addIndex($index);
        $this->assertCount(1, $entity->getIndices());

        $entity->addIndex($index);
    }

    public function testCreateIndex()
    {
        $entity = new Entity();
        $field1 = $this->getFieldMock('id_mock');
        $field2 = $this->getFieldMock('foo_mock');
        $entity->addFields([$field1, $field2]);
        $entity->createIndex('idx_foo_bar', [$field1, $field2]);

        $this->assertTrue($entity->hasIndex('idx_foo_bar'));
        $this->assertTrue($entity->isIndex([$field1, $field2]));
    }

    public function testIsIndex()
    {
        $entity = new Entity();
        $field1 = new Field('category_id');
        $field2 = new Field('type');
        $entity->addField($field1);
        $entity->addField($field2);

        $index = new Index('test_index');
        $index->addFields([$field1, $field2]);
        $entity->addIndex($index);

        $this->assertTrue($entity->isIndex(['category_id', 'type']));
        $this->assertTrue($entity->isIndex(['type', 'category_id']));
        $this->assertFalse($entity->isIndex(['category_id', 'type2']));
        $this->assertFalse($entity->isIndex(['asd']));
    }

    public function testRemoveIndex()
    {
        $entity = new Entity();
        $index = $this->getIndexMock('idx_fake', ['entity' => $entity]);
        $entity->addIndex($index);
        $this->assertTrue($entity->hasIndex('idx_fake'));

        $entity->removeIndex('idx_fake');

        $this->assertFalse($entity->hasIndex('idx_fake'));
    }

    public function testAddUniqueIndex()
    {
        $entity = new Entity();
        $entity->addUnique($this->getUniqueIndexMock('author_unq'));

        $this->assertCount(1, $entity->getUnices());
    }

    public function testRemoveUniqueIndex()
    {
        $entity = new Entity();
        $unique = $this->getUniqueIndexMock('author_unq', ['entity' => $entity]);
        $entity->addUnique($unique);
        $this->assertCount(1, $entity->getUnices());

        $entity->removeUnique('author_unq');

        $this->assertCount(0, $entity->getUnices());
    }

    public function testIsUnique()
    {
        $entity = new Entity();
        $field1 = $this->getFieldMock('category_id');
        $field2 = $this->getFieldMock('type');
        $entity->addField($field1);
        $entity->addField($field2);

        $unique = new Unique('test_unique');
        $unique->addFields([$field1, $field2]);
        $entity->addUnique($unique);

        $this->assertTrue($entity->isUnique(['category_id', 'type']));
        $this->assertTrue($entity->isUnique(['type', 'category_id']));
        $this->assertFalse($entity->isUnique(['category_id', 'type2']));
        $this->assertTrue($entity->isUnique([$field1, $field2]));
        $this->assertTrue($entity->isUnique([$field2, $field1]));
    }

    public function testIsUniqueWhenUniqueField()
    {
        $entity = new Entity();
        $field = $this->getFieldMock('unique_id',['entity' => $entity, 'unique' => true]);
        $entity->addField($field);
        $this->assertTrue($entity->isUnique([$field]));
    }

    public function testIsUniquePrimaryKey()
    {
        $entity = new Entity();
        $field = $this->getFieldMock('id', ['primary' => true, 'entity' => $entity]);

        $entity->addField($field);
        $this->assertTrue($entity->isUnique(['id']));
        $this->assertTrue($entity->isUnique([$field]));
    }

    public function testisUniqueWithCompositePrimaryKey()
    {
        $entity = new Entity();
        $field1 = $this->getFieldMock('author_id', ['primary' => true, 'entity' => $entity]);
        $field2 = $this->getFieldMock('book_id', ['primary' => true, 'entity' => $entity]);
        $field3 = $this->getFieldMock('title', ['entity' => $entity]);
        $entity->addFields([$field1, $field2, $field3]);

        $this->assertTrue($entity->isUnique(['author_id', 'book_id']));
        $this->assertTrue($entity->isUnique([$field1, $field2]));
        $this->assertFalse($entity->isUnique(['author_id', 'title']));
        $this->assertFalse($entity->isUnique([$field2, $field3]));
    }

    public function testGetCompositePrimaryKey()
    {
        $field1 = $this->getFieldMock('book_id', ['primary' => true]);
        $field2 = $this->getFieldMock('author_id', ['primary' => true]);
        $field3 = $this->getFieldMock('rank');

        $entity = new Entity();
        $entity->setIdMethod('native');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);

        $this->assertCount(2, $entity->getPrimaryKey());
        $this->assertFalse($entity->hasAutoIncrementPrimaryKey());
        $this->assertNull($entity->getAutoIncrementPrimaryKey());
        $this->assertTrue($entity->hasPrimaryKey());
        $this->assertTrue($entity->hasCompositePrimaryKey());
        $this->assertSame($field1, $entity->getFirstPrimaryKeyField());
    }

    public function testGetSinglePrimaryKey()
    {
        $field1 = $this->getFieldMock('id', ['primary' => true]);
        $field2 = $this->getFieldMock('title');
        $field3 = $this->getFieldMock('isbn');

        $entity = new Entity();
        $entity->setIdMethod('native');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);

        $this->assertCount(1, $entity->getPrimaryKey());
        $this->assertFalse($entity->hasAutoIncrementPrimaryKey());
        $this->assertNull($entity->getAutoIncrementPrimaryKey());
        $this->assertTrue($entity->hasPrimaryKey());
        $this->assertFalse($entity->hasCompositePrimaryKey());
        $this->assertSame($field1, $entity->getFirstPrimaryKeyField());
    }

    public function testGetNoPrimaryKey()
    {
        $field1 = $this->getFieldMock('id');
        $field2 = $this->getFieldMock('title');
        $field3 = $this->getFieldMock('isbn');

        $entity = new Entity();
        $entity->setIdMethod('none');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);

        $this->assertCount(0, $entity->getPrimaryKey());
        $this->assertFalse($entity->hasAutoIncrementPrimaryKey());
        $this->assertNull($entity->getAutoIncrementPrimaryKey());
        $this->assertFalse($entity->hasPrimaryKey());
        $this->assertFalse($entity->hasCompositePrimaryKey());
        $this->assertNull($entity->getFirstPrimaryKeyField());
    }

    public function testGetAutoIncrementPrimaryKey()
    {
        $field1 = $this->getFieldMock('id', [
            'primary' => true,
            'auto_increment' => true
        ]);

        $field2 = $this->getFieldMock('title');
        $field3 = $this->getFieldMock('isbn');

        $entity = new Entity();
        $entity->setIdMethod('native');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);

        $this->assertCount(1, $entity->getPrimaryKey());
        $this->assertTrue($entity->hasPrimaryKey());
        $this->assertTrue($entity->hasAutoIncrementPrimaryKey());
        $this->assertSame($field1, $entity->getAutoIncrementPrimaryKey());
    }

    public function testAddIdMethodParameter()
    {
        $parameter = $this
            ->getMockBuilder('Propel\Generator\Model\IdMethodParameter')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $parameter
            ->expects($this->once())
            ->method('setEntity')
        ;

        $entity = new Entity();
        $entity->addIdMethodParameter($parameter);

        $this->assertCount(1, $entity->getIdMethodParameters());
    }

    public function testAddReferrerRelation()
    {
        $entity = new Entity('books');
        $entity->addReferrer($this->getRelationMock());

        $this->assertCount(1, $entity->getReferrers());
    }

    public function testAddRelation()
    {
        $fk = $this->getRelationMock('fk_author_id', [
            'target' => 'authors',
        ]);

        $entity = new Entity('books');
        $entity->addRelation($fk);
        $this->assertCount(1, $entity->getRelations());
        $this->assertTrue($entity->hasRelations());
        $this->assertTrue($entity->getForeignEntityNames()->search(function($elem) {
            return 'authors' === $elem;
        }));
    }

    public function testAddRelations()
    {
        $authorRel = $this->getRelationMock('author_id', ['target' => 'Authors']);
        $publisherRel = $this->getRelationMock('publisher_id', ['target' => 'Publishers']);
        $fks = [$authorRel, $publisherRel];
        $entity = new Entity('Books');
        $entity->addRelations($fks);
        $this->assertCount(2, $entity->getRelations());
        $this->assertTrue($entity->hasRelations());
        $this->assertSame($authorRel, $entity->getRelation('author_id'));
        $this->assertSame($publisherRel, $entity->getRelation('publisher_id'));
    }

    public function testGetRelationsReferencingEntity()
    {
        $fk1 = $this->getRelationMock('fk1', ['target' => 'authors']);
        $fk2 = $this->getRelationMock('fk2', ['target' => 'categories']);
        $fk3 = $this->getRelationMock('fk1', ['target' => 'authors']);

        $entity = new Entity();
        $entity->addRelation($fk1);
        $entity->addRelation($fk2);
        $entity->addRelation($fk3);

        $this->assertCount(2, $entity->getRelationsReferencingEntity('authors'));
    }

    public function testGetFieldRelations()
    {
        $fk1 = $this->getRelationMock('fk1', [
            'local_fields' => ['foo', 'author_id', 'bar']
        ]);

        $fk2 = $this->getRelationMock('fk2', [
            'local_fields' => ['foo', 'bar']
        ]);

        $entity = new Entity();
        $entity->addRelation($fk1);
        $entity->addRelation($fk2);

        $this->assertCount(1, $entity->getFieldRelations('author_id'));
        $this->assertContains($fk1, $entity->getFieldRelations('author_id'));
    }

    public function testSetAlias()
    {
        $entity = new Entity('books');

        $this->assertFalse($entity->isAlias());

        $entity->setAlias('Book');
        $this->assertTrue($entity->isAlias());
        $this->assertSame('Book', $entity->getAlias());
    }

    public function testSetContainsForeignPK()
    {
        $entity = new Entity();

        $entity->setContainsForeignPK(true);
        $this->assertTrue($entity->getContainsForeignPK());
    }

    public function testSetCrossReference()
    {
        $entity = new Entity('books');

        $this->assertFalse($entity->isCrossRef());
        $this->assertFalse($entity->isCrossRef());

        $entity->setCrossRef(true);
        $this->assertTrue($entity->isCrossRef());
        $this->assertTrue($entity->isCrossRef());
    }

    public function testSetSkipSql()
    {
        $entity = new Entity('books');
        $entity->setSkipSql(true);

        $this->assertTrue($entity->isSkipSql());
    }

    public function testSetForReferenceOnly()
    {
        $entity = new Entity('books');
        $entity->setForReferenceOnly(true);

        $this->assertTrue($entity->isForReferenceOnly());
    }

    public function testSetDatabaseWhenEntityBelongsToDifferentDatabase()
    {
        $db1 = new Database('bookstore1');
        $db2 =new Database('bookstore2');
        $entity = new Entity('Book');
        $db1->addEntity($entity);
        $entity->setDatabase($db2);

        $this->assertSame($db2, $entity->getDatabase());
    }

    public function testGetAutoincrementFieldNames()
    {
        $entity= new Entity();
        $field1 = $this->getFieldMock('author_id', ['entity' => $entity, 'auto_increment' => true]);
        $field2 = $this->getFieldMock('book_id', ['entity' => $entity, 'auto_increment' => true]);
        $entity->addFields([$field1, $field2]);

        $this->assertEquals(['author_id', 'book_id'], $entity->getAutoIncrementFieldNames());
    }

    public function testHasAutoincrement()
    {
        $entity1 = new Entity();
        $field1 = $this->getFieldMock('id', ['auto_increment' => true, 'entity' => $entity1]);
        $entity1->addField($field1);

        $this->assertTrue($entity1->hasAutoIncrement());

        $entity2 = new Entity();
        $field2 = $this->getFieldMock('title', ['entity' => $entity1]);
        $entity2->addField($field2);

        $this->assertFalse($entity2->hasAutoIncrement());
    }

    public function testQuoteIdentifier()
    {
        $database = $this->getDatabaseMock('test_db', ['platform' => new SqlitePlatform()]);
        $entity = new Entity();
        $entity->setDatabase($database);
        $entity->setIdentifierQuoting(true);
        $this->assertTrue($entity->isIdentifierQuotingEnabled());
        $this->assertEquals('[text]', $entity->quoteIdentifier('text'));
    }

    public function testNoQuoteIdentifier()
    {
        $database = $this->getDatabaseMock('test_db', ['platform' => new SqlitePlatform()]);
        $entity = new Entity();
        $entity->setDatabase($database);
        $entity->setIdentifierQuoting(false);
        $this->assertFalse($entity->isIdentifierQuotingEnabled());
        $this->assertEquals('text', $entity->quoteIdentifier('text'));
    }

    public function testGetIdentifierQuoting()
    {
        $entity = new Entity();
        $this->assertNull($entity->getIdentifierQuoting());
        $entity->setIdentifierQuoting(true);
        $this->assertTrue($entity->getIdentifierQuoting());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\RuntimeException
     */
    public function testQuoteIdentifierNoPlatform()
    {
        $entity = new Entity();
        $database = $this->getDatabaseMock('test_db');
        $entity->setDatabase($database);
        $entity->quoteIdentifier('text');
    }

    public function testClone()
    {
        $entity = new Entity('Book');
        $entity->addField($this->getFieldMock('id', ['entity' => $entity, 'primary' => true, 'auto_increment' => true]));
        $entity->addField($this->getFieldMock('title', ['entity' => $entity]));
        $entity->addField($this->getFieldMock('children', ['entity' => $entity, 'inheritance' => true]));
        $entity->addRelation($this->getRelationMock('Rel1', ['entity' => $entity]));

        $clone = clone $entity;

        $this->assertEquals($entity, $clone, 'Entities are equals');
        $this->assertNotSame($entity, $clone, 'Entities are different objects');
        $this->assertEquals($entity->getFields(), $clone->getFields(), 'Field sets are equals');
        $this->assertNotSame($entity->getFields(), $clone->getFields(), 'Field sets are not the same object');

        foreach ($entity->getFields() as $field) {
            $cloneField = $clone->getFieldByName($field->getName());
            $this->assertNotNull($cloneField, 'Cloned set contains the given field');
            $this->assertNotSame($field, $cloneField, 'Fields are different objects');
        }

        $this->assertEquals($entity->getChildrenField(), $clone->getChildrenField());
        $this->assertNotSame($entity->getChildrenField(), $clone->getChildrenField());
        $this->assertEquals($entity->getRelation('Rel1'), $clone->getRelation('Rel1'));
        $this->assertNotSame($entity->getRelation('Rel1'), $clone->getRelation('Rel1'));
    }

    public function testGetCrossRelation()
    {
        $user = new Entity('User');
        $user->addField($this->getFieldMock('id', ['entity' => $user, 'primary' => true, 'required' => true]));
        $user->addField($this->getFieldMock('name', ['entity' => $user]));

        $role = new Entity('Role');
        $role->addField($this->getFieldMock('id', ['entity' => $role, 'primary' => true, 'required' => true]));
        $role->addField($this->getFieldMock('role', ['entity' => $role]));

        $userXrole = new Entity('UserXRole');
        $userXrole->addField($this->getFieldMock('user_id', ['entity' => $userXrole, 'primary' => true, 'required' => true]));
        $userXrole->addField($this->getFieldMock('role_id', ['entity' => $userXrole, 'primary' => true, 'required' => true]));
        $userXrole->setCrossRef(true);

        $rel1 = new Relation();
        $rel1->setEntity($userXrole);
        $rel1->setForeignEntity($user);
        $rel1->addReference('user_id', 'id');
        $userXrole->addRelation($rel1);
        $user->addReferrer($rel1);

        $rel2 = new Relation();
        $rel2->setEntity($userXrole);
        $rel2->setForeignEntity($role);
        $rel2->addReference('role_id', 'id');
        $userXrole->addRelation($rel2);
        $role->addReferrer($rel2);

        $crossRels = $user->getCrossRelations();

        $this->assertCount(1, $crossRels);
        $this->assertInstanceOf(CrossRelation::class, $crossRels[0]);
        $this->assertTrue($user->hasCrossRelations());
        $this->assertTrue($role->hasCrossRelations());
    }

    /**
     * Returns a dummy Field object.
     *
     * @param  string $name    The field name
     * @param  array  $options An array of options
     * @return Field
     */
    protected function getFieldMock($name, array $options = [])
    {
        $defaults = [
            'primary' => false,
            'auto_increment' => false,
            'inheritance' => false,
            'lazy' => false,
            'phpName' => NamingTool::toStudlyCase($name),
            'pg_transaction' => true,
            'unique' => false,
            'required' => false
        ];

        //Overwrite default options with custom options
        $options = array_merge($defaults, $options);

        $field = parent::getFieldMock($name, $options);

        $field
            ->expects($this->any())
            ->method('setEntity')
        ;

        $field
            ->expects($this->any())
            ->method('setPosition')
        ;

        $field
            ->expects($this->any())
            ->method('isPrimaryKey')
            ->will($this->returnValue($options['primary']))
        ;

        $field
            ->expects($this->any())
            ->method('isAutoIncrement')
            ->will($this->returnValue($options['auto_increment']))
        ;

        $field
            ->expects($this->any())
            ->method('isInheritance')
            ->will($this->returnValue($options['inheritance']))
        ;

        $field
            ->expects($this->any())
            ->method('isLazyLoad')
            ->will($this->returnValue($options['lazy']))
        ;

        $field
            ->expects($this->any())
            ->method('requiresTransactionInPostgres')
            ->will($this->returnValue($options['pg_transaction']))
        ;
        $field
            ->expects($this->any())
            ->method('isUnique')
            ->will($this->returnValue($options['unique']))
        ;
        $field
            ->expects($this->any())
            ->method('isNotNull')
            ->will($this->returnValue($options['required']))
        ;

        return $field;
    }
}
