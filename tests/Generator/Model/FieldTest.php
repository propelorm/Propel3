<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Common\Types\FieldTypeInterface;
use Propel\Common\Types\SQL\VarcharType;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

/**
 * Tests for package handling.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class FieldTest extends ModelTestCase
{
    public function testCreateNewField(): void
    {
        $field = new Field('title');
        $entity = $this->getEntityMock('FakeEntity');
        $field->setEntity($entity);

        $this->assertSame('title', $field->getName()->toString());
        $this->assertEmpty($field->getAutoIncrementString());
        $this->assertSame('FIELD_TITLE', $field->getConstantName()->toString());
        $this->assertSame('public', $field->getMutatorVisibility());
        $this->assertSame('public', $field->getAccessorVisibility());
        $this->assertEquals(0, $field->getSize());
        $this->assertTrue($field->getReferrers()->isEmpty());
        $this->assertFalse($field->isAutoIncrement());
        $this->assertFalse($field->isEnumeratedClasses());
        $this->assertFalse($field->isLazyLoad());
        $this->assertFalse($field->isNamePlural());
        $this->assertFalse($field->isNotNull());
        $this->assertFalse($field->isPrimaryKey());
        $this->assertFalse($field->isPrimaryString());
        $this->assertFalse($field->isUnique());
        $this->assertFalse($field->requiresTransactionInPostgres());
        $this->assertNull($field->getPlatform());
    }

    public function testGetNullDefaultValueString(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getDefaultValue')
            ->will($this->returnValue(null))
        ;

        $field = new Field();
        $field->setDomain($domain);

        $this->assertSame('null', $field->getDefaultValueString());
    }

    /**
     * @dataProvider provideDefaultValues
     */
    public function testGetDefaultValueString($mappingType, $value, $expected): void
    {
        $defaultValue = $this
            ->getMockBuilder('Propel\Generator\Model\FieldDefaultValue')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $defaultValue
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($value))
        ;

        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getDefaultValue')
            ->will($this->returnValue($defaultValue))
        ;
        $domain
            ->expects($this->any())
            ->method('setDefaultValue')
        ;
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setDefaultValue('foo');          // Test with a scalar
        $field->setDefaultValue($defaultValue);  // Test with an object

        $this->assertSame($expected, $field->getDefaultValueString());
    }

    public function provideDefaultValues(): array
    {
        return [
            ['DOUBLE', 3.14, '3.14'],
            ['VARCHAR', 'hello', "'hello'"],
            ['VARCHAR', "john's bike", "'john\\'s bike'"],
            ['BOOLEAN', 1, 'true'],
            ['BOOLEAN', 0, 'false'],
            ['ENUM', 'foo,bar', "'foo,bar'"],
        ];
    }

    public function testAddInheritance(): void
    {
        $field = new Field();

        $inheritance = $this
            ->getMockBuilder('Propel\Generator\Model\Inheritance')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $inheritance
            ->expects($this->any())
            ->method('setField')
            ->with($this->equalTo($field))
        ;

        $field->addInheritance($inheritance);

        $this->assertTrue($field->isEnumeratedClasses());
        $this->assertEquals(1, $field->getChildren()->size());

        $field->clearInheritanceList();
        $this->assertCount(0, $field->getChildren());
    }

    public function testIsDefaultSqlTypeFromDomain(): void
    {
        $toCopy = $this->getDomainMock();
        $toCopy
            ->expects($this->once())
            ->method('getSqlType')
            ->will($this->returnValue('INTEGER'))
        ;

        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->any())
            ->method('getDomainForType')
            ->with($this->equalTo('BOOLEAN'))
            ->will($this->returnValue($toCopy))
        ;

        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('copy')
            ->with($this->equalTo($toCopy))
        ;
        $domain
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('BOOLEAN'))
        ;
        $domain
            ->expects($this->any())
            ->method('getSqlType')
            ->will($this->returnValue('INTEGER'))
        ;

        $field = new Field();
        $field->setEntity($this->getEntityMock('books', [
            'platform' => $platform
        ]));
        $field->setDomain($domain);
        $field->setDomainForType('BOOLEAN');

        $this->assertTrue($field->isDefaultSqlType($platform));
    }

    public function testIsDefaultSqlType(): void
    {
        $field = new Field();

        $this->assertTrue($field->isDefaultSqlType());
    }

    public function testGetNotNullString(): void
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getNotNullString')
            ->will($this->returnValue('NOT NULL'))
        ;

        $entity = $this->getEntityMock('books', ['platform' => $platform]);

        $field = new Field();
        $field->setEntity($entity);
        $field->setNotNull(true);

        $this->assertSame('NOT NULL', $field->getNotNullString());
    }

    /**
     * @dataProvider providePdoTypes
     *
     */
    public function testGetPdoType($mappingType, $pdoType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame($pdoType, $field->getPDOType());
    }

    public function providePdoTypes(): array
    {
        return [
            ['CHAR', \PDO::PARAM_STR],
            ['VARCHAR', \PDO::PARAM_STR],
            ['LONGVARCHAR', \PDO::PARAM_STR],
            ['CLOB', \PDO::PARAM_STR],
            ['CLOB_EMU', \PDO::PARAM_STR],
            ['NUMERIC', \PDO::PARAM_INT],
            ['DECIMAL', \PDO::PARAM_STR],
            ['TINYINT', \PDO::PARAM_INT],
            ['SMALLINT', \PDO::PARAM_INT],
            ['INTEGER', \PDO::PARAM_INT],
            ['BIGINT', \PDO::PARAM_INT],
            ['REAL', \PDO::PARAM_STR],
            ['FLOAT', \PDO::PARAM_STR],
            ['DOUBLE', \PDO::PARAM_STR],
            ['BINARY', \PDO::PARAM_STR],
            ['VARBINARY', \PDO::PARAM_LOB],
            ['LONGVARBINARY', \PDO::PARAM_LOB],
            ['BLOB', \PDO::PARAM_LOB],
            ['DATE', \PDO::PARAM_STR],
            ['TIME', \PDO::PARAM_STR],
            ['TIMESTAMP', \PDO::PARAM_STR],
            ['BOOLEAN', \PDO::PARAM_BOOL],
            ['BOOLEAN_EMU', \PDO::PARAM_INT],
            ['OBJECT', \PDO::PARAM_STR],
            ['ARRAY', \PDO::PARAM_STR],
            ['ENUM', \PDO::PARAM_STR],
            ['BU_DATE', \PDO::PARAM_STR],
            ['BU_TIMESTAMP', \PDO::PARAM_STR],
        ];
    }

    public function testEnumType(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('ENUM'))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType('ENUM');
        $field->setValueSet(['FOO', 'BAR']);

        $this->assertSame('string', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isEnumType());
        $this->assertContains('FOO', $field->getValueSet());
        $this->assertContains('BAR', $field->getValueSet());
    }

    public function testSetStringValueSet(): void
    {
        $field = new Field();
        $field->setValueSet(' FOO , BAR , BAZ');

        $this->assertContains('FOO', $field->getValueSet());
        $this->assertContains('BAR', $field->getValueSet());
        $this->assertContains('BAZ', $field->getValueSet());
    }

    public function testPhpObjectType(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('OBJECT'))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType('OBJECT');

        $this->assertFalse($field->isPhpPrimitiveType());
        $this->assertTrue($field->isPhpObjectType());
    }

    /**
     * @dataProvider provideMappingTemporalTypes
     */
    public function testTemporalType($mappingType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame('string', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isTemporalType());
    }

    public function provideMappingTemporalTypes(): array
    {
        return [
            ['DATE'],
            ['TIME'],
            ['TIMESTAMP'],
            ['BU_DATE'],
            ['BU_TIMESTAMP'],
        ];
    }

    /**
     * @dataProvider provideMappingLobTypes
     */
    public function testLobType($mappingType, $phpType, $isPhpPrimitiveType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame($phpType, $field->getPhpType());
        $this->assertSame($isPhpPrimitiveType, $field->isPhpPrimitiveType());
        $this->assertTrue($field->isLobType());
    }

    public function provideMappingLobTypes(): array
    {
        return [
            ['VARBINARY', 'string', true],
            ['LONGVARBINARY', 'string', true],
            ['BLOB', 'resource', false],
        ];
    }

    /**
     * @dataProvider provideMappingBooleanTypes
     */
    public function testBooleanType($mappingType):  void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame('boolean', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isBooleanType());
    }

    public function provideMappingBooleanTypes():  array
    {
        return [
            ['BOOLEAN'],
            ['BOOLEAN_EMU'],
        ];
    }

    /**
     * @dataProvider provideMappingNumericTypes
     */
    public function testNumericType($mappingType, $phpType, $isPrimitiveNumericType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame($phpType, $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertSame($isPrimitiveNumericType, $field->isPhpPrimitiveNumericType());
        $this->assertTrue($field->isNumericType());
    }

    public function provideMappingNumericTypes(): array
    {
        return [
            ['SMALLINT', 'int', true],
            ['TINYINT', 'int', true],
            ['INTEGER', 'int', true],
            ['BIGINT', 'string', false],
            ['FLOAT', 'double', true],
            ['DOUBLE', 'double', true],
            ['NUMERIC', 'string', false],
            ['DECIMAL', 'string', false],
            ['REAL', 'double', true],
        ];
    }

    /**
     * @dataProvider provideMappingTextTypes
     */
    public function testTextType($mappingType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame('string', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isTextType());
    }

    public function provideMappingTextTypes(): array
    {
        return [
            ['CHAR'],
            ['VARCHAR'],
            ['LONGVARCHAR'],
            ['CLOB'],
            ['DATE'],
            ['TIME'],
            ['TIMESTAMP'],
            ['BU_DATE'],
            ['BU_TIMESTAMP'],
        ];
    }

    public function testGetSizeDefinition(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('getSizeDefinition')
            ->will($this->returnValue('(10,2)'))
        ;

        $field = new Field();
        $field->setDomain($domain);

        $this->assertSame('(10,2)', $field->getSizeDefinition());
    }

    public function testGetConstantName(): void
    {
        $entity = $this->getEntityMock('Article');

        $field = new Field('created_at');
        $field->setEntity($entity);
        $field->setColumnName('created_at');

        $this->assertEquals('created_at', $field->getColumnName());
        $this->assertEquals('FIELD_CREATED_AT', $field->getConstantName());
        $this->assertEquals('ArticleEntityMap::FIELD_CREATED_AT', $field->getFullConstantName());
    }

    public function testSetDefaultPhpName(): void
    {
        $field = new Field('createdAt');

        $this->assertEquals('createdAt', $field->getName());
        $this->assertEquals('created_at', $field->getColumnName());
    }

    public function testSetCustomPhpName(): void
    {
        $field = new Field('creeeatedAt');
        $field->setName('createdAt');

        $this->assertEquals('createdAt', $field->getName());
        $this->assertEquals('created_at', $field->getColumnName());
    }

    public function testSetDefaultMutatorAndAccessorMethodsVisibility(): void
    {
        $field = new Field();
        $field->setAccessorVisibility('foo');
        $field->setMutatorVisibility('bar');

        $this->assertSame('public', $field->getAccessorVisibility());
        $this->assertSame('public', $field->getMutatorVisibility());
    }

    public function testSetMutatorAndAccessorMethodsVisibility(): void
    {
        $field = new Field();
        $field->setAccessorVisibility('private');
        $field->setMutatorVisibility('private');

        $this->assertSame('private', $field->getAccessorVisibility());
        $this->assertSame('private', $field->getMutatorVisibility());
    }

    public function testGetPhpDefaultValue(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('getPhpDefaultValue')
            ->will($this->returnValue(true))
        ;

        $field = new Field();
        $field->setDomain($domain);

        $this->assertTrue($field->getPhpDefaultValue());
    }

    public function testGetAutoIncrementStringThrowsEngineException(): void
    {
        $this->expectException('Propel\Generator\Exception\EngineException');

        $entity = $this->getEntityMock('books');
        $entity
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('none'))
        ;

        $field = new Field();
        $field->setEntity($entity);
        $field->setAutoIncrement(true);
        $field->getAutoIncrementString();
    }

    public function testGetNativeAutoIncrementString(): void
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getAutoIncrement')
            ->will($this->returnValue('AUTO_INCREMENT'))
        ;

        $entity = $this->getEntityMock('books', ['platform' => $platform]);
        $entity
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('native'))
        ;

        $field = new Field();
        $field->setAutoIncrement(true);
        $field->setEntity($entity);

        $this->assertEquals('AUTO_INCREMENT', $field->getAutoIncrementString());
    }

    public function testGetFullName(): void
    {
        $field = new Field('title');
        $field->setEntity($this->getEntityMock('books'));

        $this->assertEquals('books.TITLE', $field->getFullName());
    }

    public function testIsPhpArrayType(): void
    {
        $field = new Field();
        $this->assertFalse($field->isPhpArrayType());

        $field->setType(PropelTypes::PHP_ARRAY);
        $this->assertTrue($field->isPhpArrayType());
    }

    public function testSetSize(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setSize')
            ->with($this->equalTo(50))
        ;
        $domain
            ->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue(50))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setSize(50);

        $this->assertSame(50, $field->getSize());
    }

    public function testSetScale(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setScale')
            ->with($this->equalTo(2))
        ;
        $domain
            ->expects($this->once())
            ->method('getScale')
            ->will($this->returnValue(2))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setScale(2);

        $this->assertSame(2, $field->getScale());
    }

    public function testGetDefaultDomain(): void
    {
        $field = new Field();

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $field->getDomain());
    }

    public function testGetSingularName(): void
    {
        $field = new Field('titles');

        $this->assertEquals('title', $field->getSingularName());
        $this->assertTrue($field->isNamePlural());
    }

    public function testSetEntity(): void
    {
        $field = new Field();
        $field->setEntity($this->getEntityMock('books'));

        $this->assertInstanceOf('Propel\Generator\Model\Entity', $field->getEntity());
        $this->assertEquals('books', $field->getEntity()->getName());
    }

    public function testSetDomain(): void
    {
        $field = new Field();
        $field->setDomain($this->getDomainMock());

        $this->assertInstanceOf(Domain::class, $field->getDomain());
    }

    public function testSetDescription(): void
    {
        $field = new Field();
        $field->setDescription('Some description');

        $this->assertEquals('Some description', $field->getDescription());
    }

    public function testSetAutoIncrement(): void
    {
        $field = new Field();
        $field->setAutoIncrement(true);

        $this->assertTrue($field->isAutoIncrement());
    }

    public function testSetPrimaryString(): void
    {
        $field = new Field();
        $field->setPrimaryString(true);

        $this->assertTrue($field->isPrimaryString());
    }

    public function testSetNotNull(): void
    {
        $field = new Field();
        $field->setNotNull(true);

        $this->assertTrue($field->isNotNull());
    }

    public function testPhpSingularName(): void
    {
        $field = new Field();
        $field->setName('aliases');

        $this->assertEquals('aliases', $field->getName());
        $this->assertEquals('alias', $field->getSingularName());

        $field = new Field();
        $field->setName('Aliases');
        $field->setSingularName('Alias');

        $this->assertEquals($field->getName(), 'Aliases');
        $this->assertEquals($field->getSingularName(), 'Alias');
    }

    public function testGetMethodName(): void
    {
        $field = new Field('title');
        $this->assertEquals('Title', $field->getMethodName());
    }

    public function testSetPhpType(): void
    {
        $field = new Field('title');
        $field->setType('VARCHAR');
        $field->setPhpType('string');
        $this->assertEquals('string', $field->getPhpType());
    }

    public function testGetPosition(): void
    {
        $field = new Field('foo');
        $field->setPosition(1);

        $this->assertSame(1, $field->getPosition());
    }

    public function testGetInheritanceType(): void
    {
        $field = new Field('foo');
        $field->setInheritanceType('single');

        $this->assertEquals('single', $field->getInheritanceType());
    }

    public function testIsInheritance(): void
    {
        $field = new Field('foo');
        $field->setInheritanceType('single');
        $this->assertTrue($field->isInheritance());

        $field->setInheritanceType('false');
        $this->assertFalse($field->isInheritance());
    }

    public function testSetPrimaryKey(): void
    {
        $field= new Field('foo');
        $this->assertFalse($field->isPrimaryKey());

        $field->setPrimaryKey(true);
        $this->assertTrue($field->isPrimaryKey());
    }

    public function testGetRelations(): void
    {
        $entity = new Entity('book');
        $field = new Field('author_id');
        $field->setEntity($entity);
        $relation = $this->getRelationMock('author_fk',[
            'entity' => $entity,
            'target' => 'author',
            'local_fields' => ['author_id']
        ]);
        $entity->addRelation($relation);

        $this->assertTrue($field->isRelation());
        $this->assertSame([$relation], $field->getRelations()->toArray());
    }

    public function testHasMultipleFk(): void
    {
        $entity = new Entity('book');
        $field = new Field('author_id');
        $field->setEntity($entity);
        $relation = $this->getRelationMock('author_fk',[
            'entity' => $entity,
            'target' => 'author',
            'local_fields' => ['author_id']
        ]);
        $entity->addRelation($relation);
        $this->assertFalse($field->hasMultipleFK());

        $relation1 = $this->getRelationMock('author_fk',[
            'entity' => $entity,
            'target' => 'foo',
            'local_fields' => ['author_id']
        ]);
        $entity->addRelation($relation1);
        $this->assertTrue($field->hasMultipleFK());
    }

    public function testGetFieldType(): void
    {
        $generatorConfig = $this
            ->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $generatorConfig
            ->expects($this->any())
            ->method('getFieldType')
            ->willReturn(new VarcharType())
        ;
        $entity = $this->getEntityMock('book');
        $entity
            ->expects($this->any())
            ->method('getGeneratorConfig')
            ->willReturn($generatorConfig)
        ;

        $field = new Field('title');
        $field->setEntity($entity);

        $this->assertInstanceOf(FieldTypeInterface::class, $field->getFieldType());
    }
}
