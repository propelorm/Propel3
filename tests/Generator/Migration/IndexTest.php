<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class IndexTest extends MigrationTestCase
{
    public function testAdd()
    {
        $originXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <index>
            <index-column name="title" />
        </index>
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testRemove()
    {
        $originXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <index>
            <index-column name="title" />
        </index>
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testChange()
    {
        $originXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="uri" required="true" />
        <index>
            <index-column name="title" />
            <index-column name="uri" />
        </index>
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="uri" required="true" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testChangeName()
    {
        $originXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="uri" required="true" />
        <index name="testIndex">
            <index-column name="title" />
            <index-column name="uri" />
        </index>
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <field name="uri" required="true" />
        <index name="NewIndexName">
            <index-column name="title" />
            <index-column name="uri" />
        </index>
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    /**
     * @group mysql
     */
    public function testChangeSize()
    {
        $originXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" size="50" />
        </index>
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" size="100" />
        </index>
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testSameIndex()
    {
        $originXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" />
        </index>
        <index name="testIndex2">
            <index-column name="title" />
        </index>
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_8">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" />
        </index>
        <index name="testIndex2">
            <index-column name="title" />
        </index>
        <index name="testIndex3">
            <index-column name="title" />
        </index>
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }
}
