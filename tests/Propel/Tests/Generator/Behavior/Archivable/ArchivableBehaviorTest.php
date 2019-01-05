<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests for ArchivableBehavior class
 *
 * @author François Zaninotto
 */
class ArchivableBehaviorTest extends TestCase
{
    protected static $generatedSQL;

    public function setUp()
    {
        if (!class_exists('\ArchivableTest1')) {
            $schema = <<<EOF
<database name="archivable_behavior_test_0">

    <entity name="archivable_test_1">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="age" type="INTEGER" />
        <field name="foo_id" type="INTEGER" />
        <relation target="ArchivableTest2">
            <reference local="foo_id" foreign="id" />
        </relation>
        <index>
            <index-field name="title" />
            <index-field name="age" />
        </index>
        <behavior name="archivable" />
    </entity>

    <entity name="archivable_test_2">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <behavior name="archivable" />
    </entity>

    <entity name="archivable_test_2_archive">
        <field name="id" required="true" primaryKey="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="archivable_test_3">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="age" type="INTEGER" />
        <field name="foo_id" type="INTEGER" />
        <unique>
            <unique-field name="title" />
        </unique>
        <behavior name="archivable">
            <parameter name="log_archived_at" value="false" />
            <parameter name="archive_table" value="my_old_archivable_test_3" />
            <parameter name="archive_on_insert" value="true" />
            <parameter name="archive_on_update" value="true" />
            <parameter name="archive_on_delete" value="false" />
        </behavior>
    </entity>

    <entity name="archivable_test_4">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="age" type="INTEGER" />
        <behavior name="archivable">
            <parameter name="archive_entity" value="\Propel\Tests\Generator\Behavior\Archivable\FooArchive" />
        </behavior>
    </entity>

    <entity name="archivable_test_5">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <behavior name="archivable">
            <parameter name="archive_table" value="archivable_test_5_backup" />
            <parameter name="archive_entity" value="ArchivableTest5MyBackup" />
        </behavior>
    </entity>

</database>
EOF;

            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            self::$generatedSQL = $builder->getSQL();
            $builder->build();
        }
    }

    public function testCreatesArchiveEntity()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('ArchivableTest1Archive'));
    }

    public function testDoesNotCreateCustomArchiveEntityIfExists()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('ArchivableTest2Archive'));
    }

    public function testCanCreateCustomArchiveEntityName()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('MyOldArchivableTest3'));
        $entityMap = QuickBuilder::$configuration->getEntityMap('MyOldArchivableTest3');
        $this->assertEquals('my_old_archivable_test_3', $entityMap->getTableName());
    }

    public function testDoesNotCreateCustomArchiveEntityIfArchiveClassIsSpecified()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('Propel\Tests\Generator\Behavior\Archivable\FooArchive'));
        $entityMap = QuickBuilder::$configuration->getEntityMap('Propel\Tests\Generator\Behavior\Archivable\FooArchive');
        $this->assertEquals('foo_archive', $entityMap->getTableName());
    }

    public function testCanCreateCustomArchiveEntityNameAndPhpName()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('ArchivableTest5MyBackup'));
        $entityMap = QuickBuilder::$configuration->getEntityMap('ArchivableTest5MyBackup');
        $this->assertEquals('archivable_test_5_backup', $entityMap->getTableName());
    }

    public function testCopiesFieldsToArchiveEntity()
    {
        $entity = QuickBuilder::$configuration->getEntityMap('ArchivableTest1Archive');
        $this->assertTrue($entity->hasField('id'));
        $this->assertContains('id INTEGER NOT NULL,', self::$generatedSQL, 'copied fields are not autoincremented');
        $this->assertTrue($entity->hasField('title'));
        $this->assertTrue($entity->hasField('age'));
        $this->assertTrue($entity->hasField('fooId'));
    }

    public function testDoesNotCopyForeignKeys()
    {
        $entityMap = QuickBuilder::$configuration->getEntityMap('ArchivableTest1Archive');
        $this->assertEquals([], $entityMap->getRelations());
    }

    public function testCopiesIndices()
    {
        $expected = "CREATE INDEX archivable_test1_archive_i_853ae9 ON §archivable_test1_archive (title,age);";
        $this->assertContains($expected, self::$generatedSQL);
    }

    public function testCopiesUniquesToIndices()
    {
        $expected = "CREATE INDEX my_old_archivable_test3_i_853ae9 ON §my_old_archivable_test_3 (title);";
        $this->assertContains($expected, self::$generatedSQL);
    }

    public function testAddsArchivedAtFieldToArchiveEntityByDefault()
    {
        $entityMap = QuickBuilder::$configuration->getEntityMap('ArchivableTest1Archive');
        $this->assertTrue($entityMap->hasField('archivedAt'));
    }

    public function testDoesNotAddArchivedAtFieldToArchiveEntityIfSpecified()
    {
        $entityMap = QuickBuilder::$configuration->getEntityMap('MyOldArchivableTest3');
        $this->assertFalse($entityMap->hasField('archivedAt'));
    }

    public function testDatabaseLevelBehavior()
    {
        $schema = <<<EOF
<database name="archivable_behavior_test_0">
    <behavior name="archivable" />
    <entity name="archivable_test_01">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expectedSql = "
CREATE TABLE §archivable_test01_archive
(
    id INTEGER NOT NULL,
    title VARCHAR(100),
    archived_at TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (id)
);
";
        $this->assertContains($expectedSql, $builder->getSQL(), "Archive entity correctly created");
    }
}
