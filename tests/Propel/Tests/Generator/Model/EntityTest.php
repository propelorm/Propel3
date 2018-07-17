<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;

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

//     public function testGetGeneratorConfig()
//     {
//         $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
//             ->disableOriginalConstructor()->getMock();
//         $database = $this->getDatabaseMock('foo');

//         $database
//             ->expects($this->once())
//             ->method('getGeneratorConfig')
//             ->will($this->returnValue($config))
//         ;

//         $entity = new Entity();
//         $entity->setDatabase($database);

//         $this->assertSame($config, $entity->getGeneratorConfig());
//     }

//     public function testGetBuildProperty()
//     {
//         $entity = new Entity();
//         $this->assertEmpty($entity->getBuildProperty('propel.foo.bar'));

//         $database = $this->getDatabaseMock('bookstore');
//         $database
//             ->expects($this->once())
//             ->method('getBuildProperty')
//             ->with('propel.foo.bar')
//             ->will($this->returnValue('baz'))
//         ;

//         $entity->setDatabase($database);
//         $this->assertSame('baz', $entity->getBuildProperty('propel.foo.bar'));
//     }

//     public function testApplyBehaviors()
//     {
//         $behavior = $this->getBehaviorMock('foo');
//         $behavior
//             ->expects($this->once())
//             ->method('isEntityModified')
//             ->will($this->returnValue(false))
//         ;

//         $behavior
//             ->expects($this->once())
//             ->method('getEntityModifier')
//             ->will($this->returnValue($behavior))
//         ;

//         $behavior
//             ->expects($this->once())
//             ->method('modifyEntity')
//         ;

//         $behavior
//             ->expects($this->once())
//             ->method('setEntityModified')
//             ->with($this->equalTo(true))
//         ;

//         $entity = new Entity();
//         $entity->addBehavior($behavior);
//         $entity->applyBehaviors();
//     }

//     public function testGetAdditionalBuilders()
//     {
//         $additionalBehaviors = array(
//             $this->getBehaviorMock('foo'),
//             $this->getBehaviorMock('bar'),
//             $this->getBehaviorMock('baz'),
//         );

//         $behavior = $this->getBehaviorMock('mix', array(
//             'additional_builders' => $additionalBehaviors,
//         ));

//         $entity = new Entity();
//         $entity->addBehavior($behavior);

//         $this->assertCount(3, $entity->getAdditionalBuilders());
//         $this->assertTrue($entity->hasAdditionalBuilders());
//     }

//     public function testHasNoAdditionalBuilders()
//     {
//         $entity = new Entity();
//         $entity->addBehavior($this->getBehaviorMock('foo'));

//         $this->assertCount(0, $entity->getAdditionalBuilders());
//         $this->assertFalse($entity->hasAdditionalBuilders());
//     }

//     public function testGetFieldList()
//     {
//         $fields = array(
//             $this->getFieldMock('foo'),
//             $this->getFieldMock('bar'),
//             $this->getFieldMock('baz'),
//         );

//         $entity = new Entity();

//         $this->assertSame('foo|bar|baz', $entity->getFieldList($fields, '|'));
//     }

//     public function testGetNameWithoutPlatform()
//     {
//         $entity = new Entity('books');

//         $this->assertSame('books', $entity->getName());
//     }

//     /**
//      * @dataProvider provideSchemaNames
//      *
//      */
//     public function testGetNameWithPlatform($supportsSchemas, $schemaName, $expectedName)
//     {
//         $platform = $this->getPlatformMock($supportsSchemas);

//         $database = $this->getDatabaseMock($schemaName, array(
//             'platform' => $platform,
//         ));

//         $platform
//             ->expects($supportsSchemas ? $this->once() : $this->never())
//             ->method('getSchemaDelimiter')
//             ->will($this->returnValue('.'))
//         ;

//         $entity = new Entity('books');
//         $entity->setSchema($schemaName);
//         $entity->setDatabase($database);

//         $this->assertSame($expectedName, $entity->getFQTableName());
//     }

//     public function provideSchemaNames()
//     {
//         return array(
//             array(false, 'bookstore', 'books'),
//             array(false, null, 'books'),
//             array(true, 'bookstore', 'bookstore.books'),
//         );
//     }

//     public function testSetDefaultPhpName()
//     {
//         $entity = new Entity('CreatedAt');

//         $this->assertSame('CreatedAt', $entity->getName());
//         $this->assertSame('createdAt', $entity->getCamelCaseName());
//         $this->assertSame('created_at', $entity->getTableName());
//     }

//     public function testSetCustomPhpName()
//     {
//         $entity = new Entity('created_at');
//         $entity->setName('CreatedAt');

//         $this->assertSame('CreatedAt', $entity->getName());
//         $this->assertSame('createdAt', $entity->getCamelCaseName());
//         $this->assertSame('created_at', $entity->getTableName());
//     }

//     public function testSetDescription()
//     {
//         $entity = new Entity();

//         $this->assertFalse($entity->hasDescription());

//         $entity->setDescription('Some description');
//         $this->assertTrue($entity->hasDescription());
//         $this->assertSame('Some description', $entity->getDescription());
//     }

//     public function testSetInvalidDefaultStringFormat()
//     {
//         $this->expectException('Propel\Generator\Exception\InvalidArgumentException');

//         $entity = new Entity();
//         $entity->setDefaultStringFormat('FOO');
//     }

//     public function testGetDefaultStringFormatFromDatabase()
//     {
//         $database = $this->getDatabaseMock('bookstore');
//         $database
//             ->expects($this->once())
//             ->method('getDefaultStringFormat')
//             ->will($this->returnValue('XML'))
//         ;

//         $entity = new Entity();
//         $entity->setDatabase($database);

//         $this->assertSame('XML', $entity->getDefaultStringFormat());
//     }

//     /**
//      * @dataProvider provideStringFormats
//      *
//      */
//     public function testGetDefaultStringFormat($format)
//     {
//         $entity = new Entity();
//         $entity->setDefaultStringFormat($format);

//         $this->assertSame($format, $entity->getDefaultStringFormat());
//     }

//     public function provideStringFormats()
//     {
//         return array(
//             array('XML'),
//             array('YAML'),
//             array('JSON'),
//             array('CSV'),
//         );
//     }

//     public function testAddSameFieldTwice()
//     {
//         $entity = new Entity('books');
//         $field = $this->getFieldMock('created_at', array('phpName' => 'CreatedAt'));

//         $this->expectException('Propel\Generator\Exception\EngineException');

//         $entity->addField($field);
//         $entity->addField($field);
//     }

//     public function testGetChildrenNames()
//     {
//         $field = $this->getFieldMock('created_at', array('inheritance' => true));

//         $field
//             ->expects($this->any())
//             ->method('isEnumeratedClasses')
//             ->will($this->returnValue(true))
//         ;

//         $children[] = $this->createMock('Propel\Generator\Model\Inheritance');
//         $children[] = $this->createMock('Propel\Generator\Model\Inheritance');

//         $field
//             ->expects($this->any())
//             ->method('getChildren')
//             ->will($this->returnValue($children))
//         ;

//         $entity = new Entity('books');
//         $entity->addField($field);

//         $names = $entity->getChildrenNames();

//         $this->assertCount(2, $names);
//         $this->assertSame('Propel\Generator\Model\Inheritance', get_parent_class ($names[0]));
//         $this->assertSame('Propel\Generator\Model\Inheritance', get_parent_class ($names[1]));
//     }

//     public function testCantGetChildrenNames()
//     {
//         $field = $this->getFieldMock('created_at', array('inheritance' => true));

//         $field
//             ->expects($this->any())
//             ->method('isEnumeratedClasses')
//             ->will($this->returnValue(false))
//         ;

//         $entity = new Entity('books');
//         $entity->addField($field);

//         $this->assertNull($entity->getChildrenNames());
//     }

//     public function testAddInheritanceField()
//     {
//         $entity = new Entity('books');
//         $field = $this->getFieldMock('created_at', array('inheritance' => true));

//         $this->assertInstanceOf('Propel\Generator\Model\Field', $entity->addField($field));
//         $this->assertInstanceOf('Propel\Generator\Model\Field', $entity->getChildrenField());
//         $this->assertTrue($entity->hasField($field, true));
//         $this->assertTrue($entity->hasField($field, false));
//         $this->assertCount(1, $entity->getFields());
//         $this->assertSame(1, $entity->getNumFields());
//         $this->assertTrue($entity->requiresTransactionInPostgres());
//     }

//     public function testHasBehaviors()
//     {
//         $behavior1 = $this->getBehaviorMock('Foo');
//         $behavior2 = $this->getBehaviorMock('Bar');
//         $behavior3 = $this->getBehaviorMock('Baz');

//         $entity = new Entity();
//         $entity->addBehavior($behavior1);
//         $entity->addBehavior($behavior2);
//         $entity->addBehavior($behavior3);

//         $this->assertCount(3, $entity->getBehaviors());

//         $this->assertTrue($entity->hasBehavior('Foo'));
//         $this->assertTrue($entity->hasBehavior('Bar'));
//         $this->assertTrue($entity->hasBehavior('Baz'));
//         $this->assertFalse($entity->hasBehavior('Bab'));

//         $this->assertSame($behavior1, $entity->getBehavior('Foo'));
//         $this->assertSame($behavior2, $entity->getBehavior('Bar'));
//         $this->assertSame($behavior3, $entity->getBehavior('Baz'));
//     }

//     public function testAddField()
//     {
//         $entity = new Entity('books');
//         $field = $this->getFieldMock('createdAt');

//         $this->assertInstanceOf('Propel\Generator\Model\Field', $entity->addField($field));
//         $this->assertNull($entity->getChildrenField());
//         $this->assertTrue($entity->requiresTransactionInPostgres());
//         $this->assertTrue($entity->hasField($field));
//         $this->assertTrue($entity->hasField('createdat', true));
//         $this->assertSame($field, $entity->getField('createdAt'));
//         $this->assertCount(1, $entity->getFields());
//         $this->assertSame(1, $entity->getNumFields());
//     }

//     public function testCantRemoveFieldWhichIsNotInEntity()
//     {
//         $this->expectException('Propel\Generator\Exception\EngineException');

//         $field1 = $this->getFieldMock('title');

//         $entity = new Entity('books');
//         $entity->removeField($field1);
//     }

//     public function testRemoveFieldByName()
//     {
//         $field1 = $this->getFieldMock('id');
//         $field2 = $this->getFieldMock('title');
//         $field3 = $this->getFieldMock('isbn');

//         $entity = new Entity('books');
//         $entity->addField($field1);
//         $entity->addField($field2);
//         $entity->addField($field3);
//         $entity->removeField('title');

//         $this->assertCount(2, $entity->getFields());
//         $this->assertTrue($entity->hasField('id'));
//         $this->assertTrue($entity->hasField('isbn'));
//         $this->assertFalse($entity->hasField('title'));
//     }

//     public function testRemoveField()
//     {
//         $field1 = $this->getFieldMock('id');
//         $field2 = $this->getFieldMock('title');
//         $field3 = $this->getFieldMock('isbn');

//         $entity = new Entity('books');
//         $entity->addField($field1);
//         $entity->addField($field2);
//         $entity->addField($field3);
//         $entity->removeField($field2);

//         $this->assertCount(2, $entity->getFields());
//         $this->assertTrue($entity->hasField('id'));
//         $this->assertTrue($entity->hasField('isbn'));
//         $this->assertFalse($entity->hasField('title'));
//     }

//     public function testGetNumLazyLoadFields()
//     {
//         $field1 = $this->getFieldMock('created_at');
//         $field2 = $this->getFieldMock('updated_at', array('lazy' => true));

//         $field3 = $this->getFieldMock('deleted_at', array('lazy' => true));

//         $entity = new Entity('books');
//         $entity->addField($field1);
//         $entity->addField($field2);
//         $entity->addField($field3);

//         $this->assertSame(2, $entity->getNumLazyLoadFields());
//     }

//     public function testHasEnumFields()
//     {
//         $field1 = $this->getFieldMock('created_at');
//         $field2 = $this->getFieldMock('updated_at');

//         $field1
//             ->expects($this->any())
//             ->method('isEnumType')
//             ->will($this->returnValue(false))
//         ;

//         $field2
//             ->expects($this->any())
//             ->method('isEnumType')
//             ->will($this->returnValue(true))
//         ;

//         $entity = new Entity('books');

//         $entity->addField($field1);
//         $this->assertFalse($entity->hasEnumFields());

//         $entity->addField($field2);
//         $this->assertTrue($entity->hasEnumFields());
//     }

//     public function testCantGetField()
//     {
//         $entity = new Entity('books');

//         $this->assertFalse($entity->hasField('FOO', true));
//     }

//     /**
//      * @expectedException \InvalidArgumentException
//      */
//     public function testCantGetFieldException()
//     {
//         $entity = new Entity('books');

//         $this->assertNull($entity->getField('FOO'));
//     }

//     public function testSetAbstract()
//     {
//         $entity = new Entity();
//         $this->assertFalse($entity->isAbstract());

//         $entity->setAbstract(true);
//         $this->assertTrue($entity->isAbstract());
//     }

//     public function testAddIndex()
//     {
//         $entity = new Entity();
//         $index = new Index();
//         $index->addField(['name' => 'bla']);
//         $entity->addIndex($index);

//         $this->assertCount(1, $entity->getIndices());
//     }

//     /**
//      * @expectedException \Propel\Generator\Exception\InvalidArgumentException
//      */
//     public function testAddEmptyIndex()
//     {
//         $entity = new Entity();
//         $entity->addIndex(new Index());

//         $this->assertCount(1, $entity->getIndices());
//     }

//     public function testAddArrayIndex()
//     {
//         $entity = new Entity();
//         $entity->addIndex(array('name' => 'author_idx', 'fields' => [['name' => 'bla']]));

//         $this->assertCount(1, $entity->getIndices());
//     }

//     public function testIsIndex()
//     {
//         $entity = new Entity();
//         $field1 = new Field('category_id');
//         $field2 = new Field('type');
//         $entity->addField($field1);
//         $entity->addField($field2);

//         $index = new Index('test_index');
//         $index->setFields([$field1, $field2]);
//         $entity->addIndex($index);

//         $this->assertTrue($entity->isIndex(['category_id', 'type']));
//         $this->assertTrue($entity->isIndex(['type', 'category_id']));
//         $this->assertFalse($entity->isIndex(['category_id', 'type2']));
//         $this->assertFalse($entity->isIndex(['asd']));
//     }

//     public function testAddUniqueIndex()
//     {
//         $entity = new Entity();
//         $entity->addUnique($this->getUniqueIndexMock('author_unq'));

//         $this->assertCount(1, $entity->getUnices());
//     }

//     public function testAddArrayUnique()
//     {
//         $entity = new Entity();
//         $entity->addUnique(array('name' => 'author_unq'));

//         $this->assertCount(1, $entity->getUnices());
//     }

//     public function testGetCompositePrimaryKey()
//     {
//         $field1 = $this->getFieldMock('book_id', array('primary' => true));
//         $field2 = $this->getFieldMock('author_id', array('primary' => true));
//         $field3 = $this->getFieldMock('rank');

//         $entity = new Entity();
//         $entity->setIdMethod('native');
//         $entity->addField($field1);
//         $entity->addField($field2);
//         $entity->addField($field3);

//         $this->assertCount(2, $entity->getPrimaryKey());
//         $this->assertFalse($entity->hasAutoIncrementPrimaryKey());
//         $this->assertNull($entity->getAutoIncrementPrimaryKey());
//         $this->assertTrue($entity->hasPrimaryKey());
//         $this->assertTrue($entity->hasCompositePrimaryKey());
//         $this->assertSame($field1, $entity->getFirstPrimaryKeyField());
//     }

//     public function testGetSinglePrimaryKey()
//     {
//         $field1 = $this->getFieldMock('id', array('primary' => true));
//         $field2 = $this->getFieldMock('title');
//         $field3 = $this->getFieldMock('isbn');

//         $entity = new Entity();
//         $entity->setIdMethod('native');
//         $entity->addField($field1);
//         $entity->addField($field2);
//         $entity->addField($field3);

//         $this->assertCount(1, $entity->getPrimaryKey());
//         $this->assertFalse($entity->hasAutoIncrementPrimaryKey());
//         $this->assertNull($entity->getAutoIncrementPrimaryKey());
//         $this->assertTrue($entity->hasPrimaryKey());
//         $this->assertFalse($entity->hasCompositePrimaryKey());
//         $this->assertSame($field1, $entity->getFirstPrimaryKeyField());
//     }

//     public function testGetNoPrimaryKey()
//     {
//         $field1 = $this->getFieldMock('id');
//         $field2 = $this->getFieldMock('title');
//         $field3 = $this->getFieldMock('isbn');

//         $entity = new Entity();
//         $entity->setIdMethod('none');
//         $entity->addField($field1);
//         $entity->addField($field2);
//         $entity->addField($field3);

//         $this->assertCount(0, $entity->getPrimaryKey());
//         $this->assertFalse($entity->hasAutoIncrementPrimaryKey());
//         $this->assertNull($entity->getAutoIncrementPrimaryKey());
//         $this->assertFalse($entity->hasPrimaryKey());
//         $this->assertFalse($entity->hasCompositePrimaryKey());
//         $this->assertNull($entity->getFirstPrimaryKeyField());
//     }

//     public function testGetAutoIncrementPrimaryKey()
//     {
//         $field1 = $this->getFieldMock('id', array(
//             'primary' => true,
//             'auto_increment' => true
//         ));

//         $field2 = $this->getFieldMock('title');
//         $field3 = $this->getFieldMock('isbn');

//         $entity = new Entity();
//         $entity->setIdMethod('native');
//         $entity->addField($field1);
//         $entity->addField($field2);
//         $entity->addField($field3);

//         $this->assertCount(1, $entity->getPrimaryKey());
//         $this->assertTrue($entity->hasPrimaryKey());
//         $this->assertTrue($entity->hasAutoIncrementPrimaryKey());
//         $this->assertSame($field1, $entity->getAutoIncrementPrimaryKey());
//     }

//     public function testAddIdMethodParameter()
//     {
//         $parameter = $this
//             ->getMockBuilder('Propel\Generator\Model\IdMethodParameter')
//             ->disableOriginalConstructor()
//             ->getMock()
//         ;
//         $parameter
//             ->expects($this->once())
//             ->method('setEntity')
//         ;

//         $entity = new Entity();
//         $entity->addIdMethodParameter($parameter);

//         $this->assertCount(1, $entity->getIdMethodParameters());
//     }

//     public function testAddArrayIdMethodParameter()
//     {
//         $entity = new Entity();
//         $entity->addIdMethodParameter(array('name' => 'foo', 'value' => 'bar'));

//         $this->assertCount(1, $entity->getIdMethodParameters());
//     }

//     public function testAddReferrerRelation()
//     {
//         $entity = new Entity('books');
//         $entity->addReferrer($this->getRelationMock());

//         $this->assertCount(1, $entity->getReferrers());
//     }

//     public function testAddRelation()
//     {
//         $fk = $this->getRelationMock('fk_author_id', array(
//             'target' => 'authors',
//         ));

//         $entity = new Entity('books');

//         $this->assertInstanceOf('Propel\Generator\Model\Relation', $entity->addRelation($fk));
//         $this->assertCount(1, $entity->getRelations());
//         $this->assertTrue($entity->hasRelations());
//         $this->assertContains('authors', $entity->getForeignEntityNames());
//     }

//     public function testAddArrayRelation()
//     {
//         $entity = new Entity('books');
//         $entity->setDatabase($this->getDatabaseMock('bookstore'));

//         $fk = $entity->addRelation(array(
//             'name'         => 'fk_author_id',
//             'field'      => 'Author',
//             'refField'   => 'Books',
//             'onDelete'     => 'CASCADE',
//             'target' => 'authors',
//         ));

//         $this->assertInstanceOf('Propel\Generator\Model\Relation', $fk);
//         $this->assertCount(1, $entity->getRelations());
//         $this->assertTrue($entity->hasRelations());

//         $this->assertContains('authors', $entity->getForeignEntityNames());
//     }

//     public function testGetRelationsReferencingEntity()
//     {
//         $fk1 = $this->getRelationMock('fk1', array('target' => 'authors'));
//         $fk2 = $this->getRelationMock('fk2', array('target' => 'categories'));
//         $fk3 = $this->getRelationMock('fk1', array('target' => 'authors'));

//         $entity = new Entity();
//         $entity->addRelation($fk1);
//         $entity->addRelation($fk2);
//         $entity->addRelation($fk3);

//         $this->assertCount(2, $entity->getRelationsReferencingEntity('authors'));
//     }

//     public function testGetFieldRelations()
//     {
//         $fk1 = $this->getRelationMock('fk1', array(
//             'local_fields' => array('foo', 'author_id', 'bar')
//         ));

//         $fk2 = $this->getRelationMock('fk2', array(
//             'local_fields' => array('foo', 'bar')
//         ));

//         $entity = new Entity();
//         $entity->addRelation($fk1);
//         $entity->addRelation($fk2);

//         $this->assertCount(1, $entity->getFieldRelations('author_id'));
//         $this->assertContains($fk1, $entity->getFieldRelations('author_id'));
//     }

//     public function testSetAlias()
//     {
//         $entity = new Entity('books');

//         $this->assertFalse($entity->isAlias());

//         $entity->setAlias('Book');
//         $this->assertTrue($entity->isAlias());
//         $this->assertSame('Book', $entity->getAlias());
//     }

//     public function testEntityPrefix()
//     {
//         $database = new Database();
//         $database->loadMapping(array(
//             'name'                   => 'bookstore',
//             'defaultIdMethod'        => 'native',
//             'tablePrefix'            => 'acme_',
//             'defaultStringFormat'    => 'XML',
//         ));

//         $entity = new Entity();
//         $database->addEntity($entity);
//         $entity->loadMapping(array(
//             'name' => 'Books',
//             'tableName' => 'books'
//         ));
//         $this->assertEquals('Books', $entity->getName());
//         $this->assertEquals('acme_books', $entity->getTableName());
//     }

//     public function testSetContainsForeignPK()
//     {
//         $entity = new Entity();

//         $entity->setContainsForeignPK(true);
//         $this->assertTrue($entity->getContainsForeignPK());
//     }

//     public function testSetCrossReference()
//     {
//         $entity = new Entity('books');

//         $this->assertFalse($entity->getIsCrossRef());
//         $this->assertFalse($entity->isCrossRef());

//         $entity->setIsCrossRef(true);
//         $this->assertTrue($entity->getIsCrossRef());
//         $this->assertTrue($entity->isCrossRef());
//     }

//     public function testSetSkipSql()
//     {
//         $entity = new Entity('books');
//         $entity->setSkipSql(true);

//         $this->assertTrue($entity->isSkipSql());
//     }

//     public function testSetForReferenceOnly()
//     {
//         $entity = new Entity('books');
//         $entity->setForReferenceOnly(true);

//         $this->assertTrue($entity->isForReferenceOnly());
//     }

//     /**
//      * Returns a dummy Field object.
//      *
//      * @param  string $name    The field name
//      * @param  array  $options An array of options
//      * @return Field
//      */
//     protected function getFieldMock($name, array $options = array())
//     {
//         $defaults = array(
//             'primary' => false,
//             'auto_increment' => false,
//             'inheritance' => false,
//             'lazy' => false,
//             'phpName' => str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $name)))),
//             'pg_transaction' => true,
//         );

//         // Overwrite default options with custom options
//         $options = array_merge($defaults, $options);

//         $field = parent::getFieldMock($name, $options);

//         $field
//             ->expects($this->any())
//             ->method('setEntity')
//         ;

//         $field
//             ->expects($this->any())
//             ->method('setPosition')
//         ;

//         $field
//             ->expects($this->any())
//             ->method('isPrimaryKey')
//             ->will($this->returnValue($options['primary']))
//         ;

//         $field
//             ->expects($this->any())
//             ->method('isAutoIncrement')
//             ->will($this->returnValue($options['auto_increment']))
//         ;

//         $field
//             ->expects($this->any())
//             ->method('isInheritance')
//             ->will($this->returnValue($options['inheritance']))
//         ;

//         $field
//             ->expects($this->any())
//             ->method('isLazyLoad')
//             ->will($this->returnValue($options['lazy']))
//         ;

//         $field
//             ->expects($this->any())
//             ->method('requiresTransactionInPostgres')
//             ->will($this->returnValue($options['pg_transaction']))
//         ;

//         return $field;
//     }
}
