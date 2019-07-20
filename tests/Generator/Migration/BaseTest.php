<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class BaseTest extends MigrationTestCase
{
    public function testSimpleAdd()
    {
        $originXml = '
<database>
    <entity name="migration_test_0">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_0">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="charfield" type="CHAR" size="1" />
    </entity>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
    }

    public function testSimpleSize()
    {
        $originXml = '
<database>
    <entity name="migration_test_0">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" type="VARCHAR" size="50" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_0">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" type="VARCHAR" size="250" />
    </entity>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
    }

    public function testCharToChar()
    {
        $originXml = '
<database>
    <entity name="migration_test_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="charfield" type="CHAR" size="1" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="charfield" type="CHAR" size="1" />
    </entity>
</database>
';

        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
    }

    public function testScale()
    {
        $originXml = '
<database>
    <entity name="migration_test_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="credits" phpName="Credits" type="DECIMAL" size="9" scale="2" required="true"/>

    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="credits" phpName="Credits" type="DECIMAL" scale="2" required="true"/>

    </entity>
</database>
';

        $target2Xml = '
<database>
    <entity name="migration_test_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="credits" phpName="Credits" type="DECIMAL" size="10" scale="2" required="true"/>

    </entity>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
        $this->applyXmlAndTest($target2Xml);
    }

    public function testColumnRequireChange()
    {
        $originXml = '
<database>
    <entity name="migration_test_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_1">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" />
    </entity>
</database>
';

        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnTypeChangeSimple()
    {
        $originXml = '
<database>
    <entity name="migration_test_2">
        <field name="field1" type="VARCHAR" />
        <field name="field2" type="INTEGER" />
        <field name="field3" type="BOOLEAN" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_2">
        <field name="field1" type="INTEGER" />
        <field name="field2" type="VARCHAR" />
        <field name="field3" type="VARCHAR" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnTypeChangeComplex()
    {
        $originXml = '
<database>
    <entity name="migration_test_complex">
        <field name="field1" type="CHAR" />
        <field name="field2" type="LONGVARCHAR" />
        <field name="field3" type="CLOB" />

        <field name="field4" type="NUMERIC" />
        <field name="field5" type="DECIMAL" />
        <field name="field6" type="TINYINT" />
        <field name="field7" type="SMALLINT" />

        <field name="field_object" type="object" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_complex">
        <field name="field1" type="LONGVARCHAR" />

        <field name="field4" type="DECIMAL" />
        <field name="field5" type="TINYINT" />
        <field name="field6" type="SMALLINT" />

        <field name="field_object" type="object" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnTypeChangeMoreComplex()
    {
        $originXml = '
<database>
    <entity name="migration_test_3">
        <field name="field1" type="CHAR" size="5" />

        <field name="field2" type="INTEGER" size="6" />
        <field name="field3" type="BIGINT" />
        <field name="field4" type="REAL" />
        <field name="field5" type="FLOAT" />
        <field name="field6" type="DOUBLE" />

        <field name="field7" type="BINARY" />
        <field name="field8" type="VARBINARY" />
        <field name="field9" type="LONGVARBINARY" />
        <field name="field10" type="BLOB" />

        <field name="field11" type="DATE" />
        <field name="field12" type="TIME" />
        <field name="field13" type="TIMESTAMP" />

        <field name="field14" type="ENUM" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_3">
        <field name="field1" type="CHAR" size="5" />

        <field name="field2" type="INTEGER" size="12" />
        <field name="field3" type="REAL" />
        <field name="field4" type="FLOAT" />
        <field name="field5" type="DOUBLE" />
        <field name="field6" type="BIGINT" />

        <field name="field7" type="VARBINARY" />
        <field name="field8" type="LONGVARBINARY" />
        <field name="field9" type="BLOB" />
        <field name="field10" type="BINARY" />

        <field name="field11" type="TIME" />
        <field name="field12" type="TIMESTAMP" />
        <field name="field13" type="DATE" />

        <field name="field14" type="VARCHAR" size="200" />
    </entity>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnChangePrimaryKey()
    {
        $originXml = '
<database>
    <entity name="migration_test_5">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <field name="title" required="true" />
    </entity>
</database>
';

        $targetXml = '
<database>
    <entity name="migration_test_5">
        <field name="id" type="integer" />
        <field name="title" />
    </entity>
</database>
';

        $target2Xml = '
<database>
    <entity name="migration_test_5">
        <field name="id" type="integer" primaryKey="true" />
        <field name="title" />
    </entity>
</database>
';

        $target3Xml = '
<database>
    <entity name="migration_test_5">
        <field name="id" type="integer" primaryKey="true" autoIncrement="true"  />
        <field name="title" />
    </entity>
</database>
';

        $target4Xml = '
<database>
    <entity name="migration_test_5">
        <field name="id" type="integer" primaryKey="true" />
        <field name="title" />
    </entity>
</database>
';

        $target5Xml = '
<database>
    <entity name="migration_test_5">
        <field name="id" type="varchar" size="200" primaryKey="true" />
        <field name="title" required="true" type="integer" />
    </entity>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
        $this->applyXmlAndTest($target2Xml);
        $this->applyXmlAndTest($target3Xml);
        $this->applyXmlAndTest($target4Xml);
        $this->applyXmlAndTest($target5Xml);
    }
}
