<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Diff\FieldComparator;
use Propel\Generator\Model\Diff\EntityComparator;

/**
 * provider for platform migration unit tests
 */
abstract class PlatformMigrationTestProvider extends PlatformTestBase
{
    public function providerForTestGetModifyDatabaseDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="blooopoo" type="INTEGER" />
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
    <entity name="Foo3">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="yipee" type="INTEGER" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
        <field name="baz3" type="LONGVARCHAR" />
    </entity>
    <entity name="Foo4">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="yipee" type="INTEGER" />
    </entity>
    <entity name="Foo5">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="lkdjfsh" type="INTEGER" />
        <field name="dfgdsgf" type="LONGVARCHAR" />
    </entity>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);

        return [[DatabaseComparator::computeDiff($d1, $d2, $withRenaming = true)]];
    }

    public function providerForTestGetRenameEntityDDL()
    {
        return [['Foo1', 'Foo2']];
    }

    public function providerForTestGetModifyEntityDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <relation name="Foo1_fk_1" target="Foo2">
            <reference local="bar" foreign="bar" />
        </relation>
        <relation name="Foo1_fk_2" target="Foo2">
            <reference local="baz" foreign="baz" />
        </relation>
        <index name="bar_fk">
            <index-field name="bar"/>
        </index>
        <index name="bar_baz_fk">
            <index-field name="bar"/>
            <index-field name="baz"/>
        </index>
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
        <field name="baz3" type="LONGVARCHAR" />
        <relation name="foo1_fk_1" target="Foo2">
            <reference local="bar1" foreign="bar" />
        </relation>
        <index name="bar_fk">
            <index-field name="bar1"/>
        </index>
        <index name="baz_fk">
            <index-field name="baz3"/>
        </index>
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntityByName('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntityByName('Foo');

        return [[EntityComparator::computeDiff($t1, $t2)]];
    }

    public function providerForTestGetModifyEntityFieldsDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
        <field name="baz3" type="LONGVARCHAR" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntityByName('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntityByName('Foo');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareFields();

        return [[$tc->getEntityDiff()]];
    }

    public function providerForTestGetModifyEntityPrimaryKeysDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" />
        <field name="bar" type="INTEGER" primaryKey="true" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntityByName('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntityByName('Foo');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->comparePrimaryKeys();

        return [[$tc->getEntityDiff()]];
    }

    public function providerForTestGetModifyEntityIndicesDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <index name="bar_fk">
            <index-field name="bar"/>
        </index>
        <index name="bar_baz_fk">
            <index-field name="bar"/>
            <index-field name="baz"/>
        </index>
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <index name="bar_baz_fk">
            <index-field name="id"/>
            <index-field name="bar"/>
            <index-field name="baz"/>
        </index>
        <index name="baz_fk">
            <index-field name="baz"/>
        </index>
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntityByName('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntityByName('Foo');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareIndices();

        return [[$tc->getEntityDiff()]];
    }

    public function providerForTestGetModifyEntityRelationsDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <relation name="foo1_fk_1" target="Foo2">
            <reference local="bar" foreign="bar" />
        </relation>
        <relation name="foo1_fk_2" target="Foo2">
            <reference local="bar" foreign="bar" />
            <reference local="baz" foreign="baz" />
        </relation>
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <relation name="foo1_fk_2" target="Foo2">
            <reference local="bar" foreign="bar" />
            <reference local="id" foreign="id" />
        </relation>
        <relation name="foo1_fk_3" target="Foo2">
            <reference local="baz" foreign="baz" />
        </relation>
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntityByName('Foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntityByName('Foo1');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareRelations();

        return [[$tc->getEntityDiff()]];
    }

    public function providerForTestGetModifyEntityRelationsSkipSqlDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <relation name="foo1_fk_1" target="Foo2">
            <reference local="bar" foreign="bar" />
        </relation>
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <relation name="foo1_fk_1" target="Foo2" skipSql="true">
            <reference local="bar" foreign="bar" />
        </relation>
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntityByName('Foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntityByName('Foo1');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareRelations();

        return [[$tc->getEntityDiff()]];
    }

    public function providerForTestGetModifyEntityRelationsSkipSql2DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <relation name="foo1_fk_1" target="Foo2" skipSql="true">
            <reference local="bar" foreign="bar" />
        </relation>
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
    <entity name="Foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntityByName('Foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntityByName('Foo1');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareRelations();

        return [[$tc->getEntityDiff()]];
    }

    public function providerForTestGetRemoveFieldDDL()
    {
        $table = new Entity('Foo');
        $table->setIdentifierQuoting(true);
        $column = new Field('bar');
        $table->addField($column);

        return [[$column]];
    }

    public function providerForTestGetRenameFieldDDL()
    {
        $t1 = new Entity('Foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar1');
        $c1->getDomain()->setType('DOUBLE');
        $c1->getDomain()->setSqlType('DOUBLE');
        $c1->getDomain()->setSize(2);
        $t1->addField($c1);

        $t2 = new Entity('Foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field('bar2');
        $c2->getDomain()->setType('DOUBLE');
        $c2->getDomain()->setSqlType('DOUBLE');
        $c2->getDomain()->setSize(2);
        $t2->addField($c2);

        return [[$c1, $c2]];
    }

    public function providerForTestGetModifyFieldDDL()
    {
        $t1 = new Entity('Foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->setSize(2);
        $t1->addField($c1);
        $t2 = new Entity('Foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field('bar');
        $c2->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c2->getDomain()->setSize(3);
        $t2->addField($c2);

        return [[FieldComparator::computeDiff($c1, $c2)]];
    }

    public function providerForTestGetModifyFieldsDDL()
    {
        $t1 = new Entity('Foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar1');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->setSize(2);
        $t1->addField($c1);
        $c2 = new Field('bar2');
        $c2->getDomain()->setType('INTEGER');
        $c2->getDomain()->setSqlType('INTEGER');
        $t1->addField($c2);

        $t2 = new Entity('Foo');
        $t2->setIdentifierQuoting(true);
        $t2->setIdentifierQuoting(true);
        $c3 = new Field('bar1');
        $c3->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c3->getDomain()->setSize(3);
        $t2->addField($c3);
        $c4 = new Field('bar2');
        $c4->getDomain()->setType('INTEGER');
        $c4->getDomain()->setSqlType('INTEGER');
        $c4->setNotNull(true);
        $t2->addField($c4);

        return [[[
            FieldComparator::computeDiff($c1, $c3),
            FieldComparator::computeDiff($c2, $c4)
        ]]];
    }

    public function providerForTestGetAddFieldDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $column = $this->getDatabaseFromSchema($schema)->getEntityByName('Foo')->getField('bar');

        return [[$column]];
    }

    public function providerForTestGetAddFieldsDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="bar2" type="DOUBLE" scale="2" size="3" default="-1" required="true" />
    </entity>
</database>
EOF;
        $table = $this->getDatabaseFromSchema($schema)->getEntityByName('Foo');

        return [[[$table->getField('bar1'), $table->getField('bar2')]]];
    }

    public function providerForTestGetModifyFieldRemoveDefaultValueDDL()
    {
        $t1 = new Entity('Test');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field();
        $c1->setName('test');
        $c1->getDomain()->setType('INTEGER');
        $c1->setDefaultValue(0);
        $t1->addField($c1);
        $t2 = new Entity('Test');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field();
        $c2->setName('test');
        $c2->getDomain()->setType('INTEGER');
        $t2->addField($c2);

        return [[FieldComparator::computeDiff($c1, $c2)]];
    }

    public function providerForTestGetModifyEntityRelationsSkipSql3DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Test">
        <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
        <field name="ref_test" type="INTEGER"/>
        <relation target="test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test" />
        </relation>
    </entity>
    <entity name="Test2">
        <field name="test" type="integer" primaryKey="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <entity name="Test">
    <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
    <field name="ref_test" type="INTEGER"/>
  </entity>
  <entity name="Test2">
    <field name="test" type="integer" primaryKey="true" />
  </entity>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $diff = DatabaseComparator::computeDiff($d1, $d2);

        return [[$diff]];
    }

    public function providerForTestGetModifyEntityRelationsSkipSql4DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Test">
        <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
        <field name="ref_test" type="INTEGER"/>
        <relation target="Test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test" />
        </relation>
    </entity>
    <entity name="Test2">
        <field name="test" type="integer" primaryKey="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <entity name="Test">
    <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
    <field name="ref_test" type="INTEGER"/>
  </entity>
  <entity name="Test2">
    <field name="test" type="integer" primaryKey="true" />
  </entity>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $diff = DatabaseComparator::computeDiff($d2, $d1);

        return [[$diff]];
    }
}
