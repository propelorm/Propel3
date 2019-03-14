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
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;

/**
 * provider for platform DDL unit tests
 */
abstract class PlatformTestProvider extends PlatformTestBase
{
    public function providerForTestGetAddEntitiesDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Book">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" required="true" />
        <field name="title" type="VARCHAR" size="255" required="true" />
        <index>
            <index-field name="title" />
        </index>
        <relation target="Author" />
    </entity>
    <entity name="Author">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" required="true" />
        <field name="firstName" type="VARCHAR" size="100" />
        <field name="lastName" type="VARCHAR" size="100" />
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddEntitiesDDLSchema()
    {
        $schema = <<<EOF
<database name="test" schema="x" identifierQuoting="true">
    <entity name="Book">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" required="true"/>
        <field name="title" type="VARCHAR" size="255" required="true" />
        <index>
            <index-field name="title" />
        </index>
        <field name="authorId" type="INTEGER"/>
        <relation target="Author" foreignSchema="y">
            <reference local="authorId" foreign="id" />
        </relation>
    </entity>
    <entity name="Author" schema="y">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" required="true"/>
        <field name="firstName" type="VARCHAR" size="100" />
        <field name="lastName" type="VARCHAR" size="100" />
    </entity>
    <entity name="BookSummary">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="bookId" required="true" type="INTEGER" />
        <field name="summary" required="true" type="LONGVARCHAR" />
        <relation target="Book" onDelete="cascade">
            <reference local="bookId" foreign="id" />
        </relation>
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddEntitiesSkipSQLDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Book" skipSql="true">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" required="true"/>
        <field name="title" type="VARCHAR" size="255" required="true" />
        <index>
            <index-field name="title" />
        </index>
        <field name="authorId" type="INTEGER"/>
        <relation target="Author">
            <reference local="authorId" foreign="id" />
        </relation>
    </entity>
    <entity name="Author" skipSql="true">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" required="true"/>
        <field name="first_name" type="VARCHAR" size="100" />
        <field name="last_name" type="VARCHAR" size="100" />
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddEntityDDLSimplePK()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo" description="This is foo table">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" required="true"/>
        <field name="bar" type="VARCHAR" size="255" required="true" />
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddEntityDDLNonIntegerPK()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo" description="This is foo entity">
        <field name="foo" primaryKey="true" type="VARCHAR" size="255" />
        <field name="bar" type="VARCHAR" size="255" required="true" />
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddEntityDDLCompositePK()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="foo" primaryKey="true" type="INTEGER" />
        <field name="bar" primaryKey="true" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="255" required="true" />
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddEntityDDLUniqueIndex()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <unique>
            <unique-field name="bar" />
        </unique>
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetAddEntityDDLSchema()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo" schema="Woopah">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;

        return [[$schema]];
    }

    public function providerForTestGetUniqueDDL()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field1 = new Field('bar1');
        $field1->getDomain()->copy(new Domain('FOOTYPE'));
        $entity->addField($field1);
        $field2 = new Field('bar2');
        $field2->getDomain()->copy(new Domain('BARTYPE'));
        $entity->addField($field2);
        $index = new Unique('babar');
        $index->addField($field1);
        $index->addField($field2);
        $entity->addUnique($index);

        return [
            [$index]
        ];
    }

    public function providerForTestGetIndicesDDL()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field1 = new Field('bar1');
        $field1->getDomain()->copy(new Domain('FOOTYPE'));
        $entity->addField($field1);
        $field2 = new Field('bar2');
        $field2->getDomain()->copy(new Domain('BARTYPE'));
        $entity->addField($field2);
        $index1 = new Index('babar');
        $index1->addField($field1);
        $index1->addField($field2);
        $entity->addIndex($index1);
        $index2 = new Index('foo_index');
        $index2->addField($field1);
        $entity->addIndex($index2);

        return [
            [$entity]
        ];
    }

    public function providerForTestGetIndexDDL()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field1 = new Field('bar1');
        $field1->getDomain()->copy(new Domain('FOOTYPE'));
        $entity->addField($field1);
        $field2 = new Field('bar2');
        $field2->getDomain()->copy(new Domain('BARTYPE'));
        $entity->addField($field2);
        $index = new Index('babar');
        $index->addField($field1);
        $index->addField($field2);
        $entity->addIndex($index);

        return [
            [$index]
        ];
    }

    public function providerForTestPrimaryKeyDDL()
    {
        $entity = new Entity('foo');
        $entity->setIdentifierQuoting(true);
        $field = new Field('bar');
        $field->setPrimaryKey(true);
        $entity->addField($field);

        return [
            [$entity]
        ];
    }

    public function providerForTestGetRelationDDL()
    {
        $db = new Database();
        $db->setIdentifierQuoting(true);
        $entity1 = new Entity('foo');
        $db->addEntity($entity1);
        $field1 = new Field('bar_id');
        $field1->getDomain()->copy(new Domain('FOOTYPE'));
        $entity1->addField($field1);

        $entity2 = new Entity('bar');
        $db->addEntity($entity2);
        $field2 = new Field('id');
        $field2->getDomain()->copy(new Domain('BARTYPE'));

        $entity2->addField($field2);

        $fk = new Relation('foo_bar_fk');
        $fk->setForeignEntityName('bar');
        $fk->addReference($field1, $field2);
        $fk->setOnDelete('CASCADE');
        $entity1->addRelation($fk);

        return [
            [$fk]
        ];
    }

    public function providerForTestGetRelationSkipSqlDDL()
    {
        $arr = self::providerForTestGetRelationDDL();
        $fk = $arr[0][0];
        $fk->setSkipSql(true);

        return [
            [$fk]
        ];
    }

    public function providerForTestGetRelationsDDL()
    {
        $db = new Database();
        $db->setIdentifierQuoting(true);
        $entity1 = new Entity('foo');
        $db->addEntity($entity1);

        $field1 = new Field('bar_id');
        $field1->getDomain()->copy(new Domain('FOOTYPE'));
        $entity1->addField($field1);

        $entity2 = new Entity('bar');
        $db->addEntity($entity2);
        $field2 = new Field('id');
        $field2->getDomain()->copy(new Domain('BARTYPE'));
        $entity2->addField($field2);

        $fk = new Relation('foo_bar_fk');
        $fk->setForeignEntityName('bar');
        $fk->addReference($field1, $field2);
        $fk->setOnDelete('CASCADE');
        $entity1->addRelation($fk);

        $field3 = new Field('baz_id');
        $field3->getDomain()->copy(new Domain('BAZTYPE'));
        $entity1->addField($field3);
        $entity3 = new Entity('baz');
        $db->addEntity($entity3);
        $field4 = new Field('id');
        $field4->getDomain()->copy(new Domain('BAZTYPE'));
        $entity3->addField($field4);

        $fk = new Relation('foo_baz_fk');
        $fk->setForeignEntityName('baz');
        $fk->addReference($field3, $field4);
        $fk->setOnDelete('SETNULL');
        $entity1->addRelation($fk);

        return [
            [$entity1]
        ];
    }
}
