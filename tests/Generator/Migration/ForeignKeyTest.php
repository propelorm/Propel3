<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class ForeignKeyTest extends MigrationTestCase
{
    public function testAdd()
    {
        $originXml = '
<database>
    <entity name="migration_test_6">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
    <entity name="migration_test_7">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="test_6_id" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_6">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
    <entity name="migration_test_7">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="test_6_id" type="integer" />
        <foreign-key foreignTable="migration_test_6" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id" />
        </foreign-key>
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testAddNotUnique()
    {
        $originXml = '
<database>
    <entity name="migration_test_6_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_6_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="id2" type="integer"/>
        <field name="title" required="true" />
    </entity>
    <entity name="migration_test_7_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="test_6_id" type="integer" />
        <foreign-key foreignTable="migration_test_6_1" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id2" />
        </foreign-key>
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testRemove()
    {
        $originXml = '
<database>
    <entity name="migration_test_6">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
    <entity name="migration_test_7">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="test_6_id" type="integer" />
        <foreign-key foreignTable="migration_test_6" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id" />
        </foreign-key>
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_6">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
    <entity name="migration_test_7">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="test_6_id" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testChange()
    {
        $originXml = '
<database>
    <entity name="migration_test_6">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="id2" type="integer" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
    <entity name="migration_test_7">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="test_6_id" type="integer" />
        <field name="test_6_id2" type="integer" />
        <foreign-key foreignTable="migration_test_6" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id" />
            <reference local="test_6_id2" foreign="id2" />
        </foreign-key>
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_6">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="id2" type="integer" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
    <entity name="migration_test_7">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="test_6_id" />
        <field name="test_6_id2" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }
}
