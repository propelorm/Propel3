<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use phootwork\lang\Text;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\CrossRelation;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity as BaseEntity;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Runtime\Exception\RuntimeException;
use function DeepCopy\deep_copy;

/**
 * Unit test suite for Entity model class.
 *
 * @author Martin Poeschl <mpoeschl@marmot.at>
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class EntityTest extends ModelTestCase
{
    public function testCreateNewEntity(): void
    {
        $entity = new Entity('books');

        $this->assertSame('books', $entity->getName()->toString());
        $this->assertFalse($entity->isAllowPkInsert());
        $this->assertFalse($entity->isCrossRef());
        $this->assertFalse($entity->isReloadOnInsert());
        $this->assertFalse($entity->isReloadOnUpdate());
        $this->assertFalse($entity->isSkipSql());
        $this->assertFalse($entity->isReadOnly());
        $this->assertSame(0, $entity->countLazyLoadFields());
        $this->assertEmpty($entity->getChildrenNames());
        $this->assertFalse($entity->hasRelations());
    }

    /**
     * @dataProvider provideNamespaces
     *
     */
    public function testSetNamespace($namespace, $expected): void
    {
        $entity = new Entity();
        $entity->setNamespace($namespace);

        $this->assertSame($expected, $entity->getNamespace()->toString());
    }

    public function provideNamespaces(): array
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

    public function testSetRepository(): void
    {
        $entity = new Entity('Book');
        $entity->setRepository('BookRepository');

        $this->assertEquals('BookRepository', $entity->getRepository());
    }

    public function testNames(): void
    {
        $entity = new Entity('Wurst\\Und\\Kaese');
        $this->assertEquals('Kaese', $entity->getName());
        $this->assertEquals('Wurst\\Und', $entity->getNamespace());


        $entity = new Entity();
        $this->assertEmpty($entity->getName()->toString());

        $entity->setName('Book');
        $this->assertEquals('Book', $entity->getName()->toString());
        $this->assertEquals('book', $entity->getTableName()->toString());

        $entity->setName('BookAuthor');
        $entity->setTableName('');
        $this->assertEquals('BookAuthor', $entity->getName()->toString());
        $this->assertEquals('book_author', $entity->getTableName()->toString());

        $entity->setTableName('book_has_author');
        $this->assertEquals('BookAuthor', $entity->getName()->toString());
        $this->assertEquals('book_has_author', $entity->getTableName()->toString());

        $entity->setScope('bookstore_');
        $this->assertEquals('bookstore_book_has_author', $entity->getScopedTableName()->toString());

        $entity->setNamespace('Bookstore');
        $this->assertEquals('Bookstore\\BookAuthor', $entity->getFullName()->toString());

        $entity = new Entity();
        $database = new Database();
        $database->setScope('bookings_');
        $database->setNamespace('Bookstore');
        $entity->setDatabase($database);

        $this->assertEquals('Bookstore', $entity->getNamespace()->toString());
        $this->assertEquals('bookings_', $entity->getScope()->toString());
    }

    public function testGetGeneratorConfig(): void
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

    public function testApplyBehaviors(): void
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

    public function testGetAdditionalBuilders(): void
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

    public function testHasNoAdditionalBuilders(): void
    {
        $entity = new Entity();
        $entity->addBehavior($this->getBehaviorMock('foo'));

        $this->assertCount(0, $entity->getAdditionalBuilders());
        $this->assertFalse($entity->hasAdditionalBuilders());
    }

    public function testGetNameWithoutPlatform(): void
    {
        $entity = new Entity('books');

        $this->assertSame('books', (string) $entity->getName());
    }

    /**
     * @dataProvider provideSchemaNames
     *
     */
    public function testGetNameWithPlatform($supportsSchemas, $schemaName, $expectedName): void
    {
        $platform = $this->getPlatformMock($supportsSchemas);
        $platform
            ->method('getSchemaDelimiter')
            ->will($this->returnValue($supportsSchemas ? '.' : ''))
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

        $this->assertEquals($expectedName, (string) $entity->getFullTableName());
    }

    public function provideSchemaNames(): array
    {
        return [
            [false, 'bookstore', 'books'],
            [false, '', 'books'],
            [true, 'bookstore', 'bookstore.books'],
        ];
    }

    public function testGetOverrideSchemaName(): void
    {
        $entity = new Entity();
        $entity->setDatabase($this->getDatabaseMock('bookstore'));
        $entity->setSchemaName('my_schema');

        $this->assertEquals('my_schema', $entity->getSchemaName());
    }

    public function testSetDescription(): void
    {
        $entity = new Entity();

        $this->assertFalse($entity->hasDescription());

        $entity->setDescription('Some description');
        $this->assertEquals('Some description', (string) $entity->getDescription());
    }

    public function testSetInvalidStringFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $entity = new Entity();
        $entity->setStringFormat('FOO');
    }

    public function testGetStringFormatFromDatabase(): void
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
    public function testGetStringFormat($format): void
    {
        $entity = new Entity();
        $entity->setStringFormat($format);

        $this->assertSame($format, $entity->getStringFormat());
    }

    public function provideStringFormats(): array
    {
        return [
            ['XML'],
            ['YAML'],
            ['JSON'],
            ['CSV'],
        ];
    }

    public function testAddSameFieldTwice(): void
    {
        $entity = new Entity('books');
        $field = $this->getFieldMock('created_at', ['phpName' => 'CreatedAt']);

        $this->expectException('Propel\Generator\Exception\EngineException');

        $entity->addField($field);
        $entity->addField($field);
    }

    public function testGetChildrenNames(): void
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

    public function testCantGetChildrenNames(): void
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

    public function testAddInheritanceField(): void
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

    public function testHasBehaviors(): void
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

    public function testUnregisterBehavior(): void
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

    public function testAddField(): void
    {
        $entity = new Entity('books');
        $field = $this->getFieldMock('createdAt');
        $entity->addField($field);
        $this->assertNull($entity->getChildrenField());
        $this->assertTrue($entity->requiresTransactionInPostgres());
        $this->assertTrue($entity->hasField($field));
        $this->assertSame($field, $entity->getFieldByName('createdAt'));
        $this->assertCount(1, $entity->getFields());
        $this->assertSame(1, $entity->countFields());
    }

    public function testCantRemoveFieldWhichIsNotInEntity(): void
    {
        $this->expectException(EngineException::class);

        $field1 = $this->getFieldMock('title');

        $entity = new Entity('books');
        $entity->removeField($field1);
    }

    public function testRemoveFieldByName(): void
    {
        $field1 = $this->getFieldMock('id');
        $field2 = $this->getFieldMock('title');
        $field3 = $this->getFieldMock('isbn');

        $entity = new Entity('books');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);
        $entity->removeFieldByName('title');

        $this->assertCount(2, $entity->getFields());
        $this->assertTrue($entity->hasFieldByName('id'));
        $this->assertTrue($entity->hasFieldByName('isbn'));
        $this->assertFalse($entity->hasFieldByName('title'));
    }

    public function testRemoveField(): void
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
        $this->assertTrue($entity->hasField($field1));
        $this->assertTrue($entity->hasField($field3));
        $this->assertFalse($entity->hasField($field2));
    }

    public function testCountLazyLoadFields(): void
    {
        $field1 = $this->getFieldMock('created_at');
        $field2 = $this->getFieldMock('updated_at', ['lazy' => true]);

        $field3 = $this->getFieldMock('deleted_at', ['lazy' => true]);

        $entity = new Entity('books');
        $entity->addField($field1);
        $entity->addField($field2);
        $entity->addField($field3);

        $this->assertSame(2, $entity->countLazyLoadFields());
    }

    public function testHasEnumFields(): void
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

    public function testCantGetField(): void
    {
        $entity = new Entity('books');

        $this->assertFalse($entity->hasFieldByName('FOO'));
    }

    public function testInexistentFieldReturnNull(): void
    {
        $entity = new Entity('books');

        $this->assertNull($entity->getFieldByName('FOO'));
    }

    public function testSetAbstract(): void
    {
        $entity = new Entity();
        $this->assertFalse($entity->isAbstract());

        $entity->setAbstract(true);
        $this->assertTrue($entity->isAbstract());
    }

    public function testAddIndex(): void
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

    public function testAddEmptyIndex(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $entity = new Entity();
        $entity->addIndex(new Index());

        $this->assertCount(1, $entity->getIndices());
    }

    public function testAddAlreadyCreatedIndex(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $index = $this->getIndexMock('idx_fake_entity');
        $entity = new Entity();
        $entity->addIndex($index);
        $this->assertCount(1, $entity->getIndices());

        $entity->addIndex($index);
    }

    public function testCreateIndex(): void
    {
        $entity = new Entity();
        $field1 = $this->getFieldMock('id_mock');
        $field2 = $this->getFieldMock('foo_mock');
        $entity->addFields([$field1, $field2]);
        $entity->createIndex('idx_foo_bar', [$field1, $field2]);

        $this->assertTrue($entity->hasIndex('idx_foo_bar'));
        $this->assertTrue($entity->isIndex([$field1, $field2]));
    }

    public function testIsIndex(): void
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

    public function testRemoveIndex(): void
    {
        $entity = new Entity();
        $field = $this->getFieldMock('field_fake', ['entity' => $entity]);
        $index = $this->getIndexMock('idx_fake', ['entity' => $entity, 'fields' => [$field]]);
        $entity->addIndex($index);
        $this->assertTrue($entity->hasIndex('idx_fake'));

        $entity->removeIndex('idx_fake');

        $this->assertFalse($entity->hasIndex('idx_fake'));
    }

    public function testAddUniqueIndex(): void
    {
        $entity = new Entity();
        $entity->addUnique($this->getUniqueIndexMock('author_unq'));

        $this->assertCount(1, $entity->getUnices());
    }

    public function testRemoveUniqueIndex(): void
    {
        $entity = new Entity();
        $unique = $this->getUniqueIndexMock('author_unq', ['entity' => $entity]);
        $entity->addUnique($unique);
        $this->assertCount(1, $entity->getUnices());

        $entity->removeUnique('author_unq');

        $this->assertCount(0, $entity->getUnices());
    }

    public function testIsUnique(): void
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

    public function testIsUniqueWhenUniqueField(): void
    {
        $entity = new Entity();
        $field = $this->getFieldMock('unique_id',['entity' => $entity, 'unique' => true]);
        $entity->addField($field);
        $this->assertTrue($entity->isUnique([$field]));
    }

    public function testIsUniquePrimaryKey(): void
    {
        $entity = new Entity();
        $field = $this->getFieldMock('id', ['primary' => true, 'entity' => $entity]);

        $entity->addField($field);
        $this->assertTrue($entity->isUnique(['id']));
        $this->assertTrue($entity->isUnique([$field]));
    }

    public function testisUniqueWithCompositePrimaryKey(): void
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

    public function testGetCompositePrimaryKey(): void
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

    public function testGetSinglePrimaryKey(): void
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

    public function testGetNoPrimaryKey(): void
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

    public function testGetAutoIncrementPrimaryKey(): void
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

    public function testAddIdMethodParameter(): void
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

    public function testAddReferrerRelation(): void
    {
        $entity = new Entity('books');
        $entity->addReferrer($this->getRelationMock());

        $this->assertCount(1, $entity->getReferrers());
    }

    public function testAddRelation(): void
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

    public function testAddRelations(): void
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

    public function testGetRelationsReferencingEntity(): void
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

    public function testGetFieldRelations(): void
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

    public function testSetAlias(): void
    {
        $entity = new Entity('books');

        $this->assertFalse($entity->isAlias());

        $entity->setAlias('Book');
        $this->assertTrue($entity->isAlias());
        $this->assertSame('Book', $entity->getAlias()->toString());
    }

    public function testSetContainsForeignPK(): void
    {
        $entity = new Entity();

        $entity->setContainsForeignPK(true);
        $this->assertTrue($entity->getContainsForeignPK());
    }

    public function testSetCrossReference(): void
    {
        $entity = new Entity('books');

        $this->assertFalse($entity->isCrossRef());
        $this->assertFalse($entity->isCrossRef());

        $entity->setCrossRef(true);
        $this->assertTrue($entity->isCrossRef());
        $this->assertTrue($entity->isCrossRef());
    }

    public function testSetSkipSql(): void
    {
        $entity = new Entity('books');
        $entity->setSkipSql(true);

        $this->assertTrue($entity->isSkipSql());
    }

    public function testSetForReferenceOnly(): void
    {
        $entity = new Entity('books');
        $entity->setForReferenceOnly(true);

        $this->assertTrue($entity->isForReferenceOnly());
    }

    public function testSetDatabaseWhenEntityBelongsToDifferentDatabase(): void
    {
        $db1 = new Database('bookstore1');
        $db2 =new Database('bookstore2');
        $entity = new Entity('Book');
        $db1->addEntity($entity);
        $entity->setDatabase($db2);

        $this->assertSame($db2, $entity->getDatabase());
    }

    public function testGetAutoincrementFieldNames(): void
    {
        $entity= new Entity();
        $field1 = $this->getFieldMock('author_id', ['entity' => $entity, 'auto_increment' => true]);
        $field2 = $this->getFieldMock('book_id', ['entity' => $entity, 'auto_increment' => true]);
        $entity->addFields([$field1, $field2]);

        $this->assertEquals(['author_id', 'book_id'], $entity->getAutoIncrementFieldNames()->toArray());
    }

    public function testHasAutoincrement(): void
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

    public function testQuoteIdentifier(): void
    {
        $database = $this->getDatabaseMock('test_db', ['platform' => new SqlitePlatform()]);
        $entity = new Entity();
        $entity->setDatabase($database);
        $entity->setIdentifierQuoting(true);
        $this->assertTrue($entity->isIdentifierQuotingEnabled());
        $this->assertEquals('[text]', $entity->quoteIdentifier('text'));
    }

    public function testNoQuoteIdentifier(): void
    {
        $database = $this->getDatabaseMock('test_db', ['platform' => new SqlitePlatform()]);
        $entity = new Entity();
        $entity->setDatabase($database);
        $entity->setIdentifierQuoting(false);
        $this->assertFalse($entity->isIdentifierQuotingEnabled());
        $this->assertEquals('text', $entity->quoteIdentifier('text'));
    }

    public function testGetIdentifierQuoting(): void
    {
        $entity = new Entity();
        $this->assertNull($entity->getIdentifierQuoting());
        $entity->setIdentifierQuoting(true);
        $this->assertTrue($entity->getIdentifierQuoting());
    }

    public function testQuoteIdentifierNoPlatform(): void
    {
        $this->expectException(RuntimeException::class);

        $entity = new Entity();
        $database = $this->getDatabaseMock('test_db');
        $entity->setDatabase($database);
        $entity->quoteIdentifier('text');
    }

    public function testClone(): void
    {
        //__clone() not supported anymore. Use myclabs/deep-copy instead
        $entity = new Entity('Book');
        $entity->addField($this->getFieldMock('id', ['entity' => $entity, 'primary' => true, 'auto_increment' => true]));
        $entity->addField($this->getFieldMock('title', ['entity' => $entity]));
        $entity->addField($this->getFieldMock('children', ['entity' => $entity, 'inheritance' => true]));
        $entity->addRelation($this->getRelationMock('Rel1', ['entity' => $entity]));

        $clone = deep_copy($entity);

        $this->assertEquals($entity, $clone, 'Entities are equals');
        $this->assertNotSame($entity, $clone, 'Entities are different objects');
        $this->assertEquals($entity->getFields(), $clone->getFields(), 'Field sets are equals');
        $this->assertNotSame($entity->getFields(), $clone->getFields(), 'Field sets are not the same object');

        /** @var Field $field */
        foreach ($entity->getFields() as $field) {
            $cloneField = $clone->getFieldByName($field->getName()->toString());
            $this->assertNotNull($cloneField, 'Cloned set contains the given field');
            $this->assertNotSame($field, $cloneField, 'Fields are different objects');
        }

        $this->assertEquals($entity->getChildrenField(), $clone->getChildrenField());
        $this->assertNotSame($entity->getChildrenField(), $clone->getChildrenField());
        $this->assertEquals($entity->getRelation('Rel1'), $clone->getRelation('Rel1'));
        $this->assertNotSame($entity->getRelation('Rel1'), $clone->getRelation('Rel1'));
    }

    public function testGetCrossRelation(): void
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
        $this->assertTrue($crossRels->search(fn($elem): bool => $elem instanceof CrossRelation));
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
    protected function getFieldMock(string $name, array $options = []): Field
    {
        $defaults = [
            'primary' => false,
            'auto_increment' => false,
            'inheritance' => false,
            'lazy' => false,
            'phpName' => Text::create($name)->toStudlyCase()->toString(),
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

class Entity extends BaseEntity
{
    /**
     * Executes behavior entity modifiers.
     * This is only for testing purposes. Model\Database calls already `modifyEntity` on each behavior.
     */
    public function applyBehaviors(): void
    {
        foreach ($this->behaviors as $behavior) {
            if (!$behavior->isEntityModified()) {
                $behavior->getEntityModifier()->modifyEntity();
                $behavior->setEntityModified(true);
            }
        }
    }
}
