<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class PrimaryKeyTest extends MigrationTestCase
{
    public function testAdd()
    {
        $originXml = '
<database>
    <entity name="migration_test_9">
        <field name="id" type="integer"/>
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_9">
        <field name="id" type="integer" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testRemove()
    {
        $originXml = '
<database>
    <entity name="migration_test_9">
        <field name="id" type="integer" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_9">
        <field name="id" type="integer"/>
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
    <entity name="migration_test_9">
        <field name="id" type="integer" primaryKey="true"/>
        <field name="title" required="true" />
        <field name="uri" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_9">
        <field name="id" type="varchar" primaryKey="true"/>
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
    <entity name="migration_test_9">
        <field name="id" type="integer" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_9">
        <field name="new_id" type="integer" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testChangeSize()
    {
        $originXml = '
<database>
    <entity name="migration_test_9">
        <field name="id" type="varchar" size="50" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_9">
        <field name="id" type="varchar" size="150" primaryKey="true"/>
        <field name="title" required="true" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }
}
