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
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\PgsqlPlatform;

/**
 *
 */
class PgsqlPlatformTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return PgsqlPlatform
     */
    protected function getPlatform()
    {
        return new PgsqlPlatform();
    }

    public function testGetSequenceNameDefault()
    {
        $table = new Entity('foo');
        $table->setIdMethod(Model::ID_METHOD_NATIVE);
        $col = new Field('bar');
        $col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $col->setAutoIncrement(true);
        $table->addField($col);
        $expected = 'foo_bar_seq';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
    }

    public function testGetSequenceNameCustom()
    {
        $table = new Entity('foo');
        $table->setIdMethod(Model::ID_METHOD_NATIVE);
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $table->addIdMethodParameter($idMethodParameter);
        $table->setIdMethod(Model::ID_METHOD_NATIVE);
        $col = new Field('bar');
        $col->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $col->setAutoIncrement(true);
        $table->addField($col);
        $expected = 'foo_sequence';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($table));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDL
     */
    public function testGetAddEntitiesDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- book
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "book" CASCADE;

CREATE TABLE "book"
(
    "id" serial NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "author_id" INTEGER,
    PRIMARY KEY ("id")
);

CREATE INDEX "book_i_853ae9" ON "book" ("title");

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "author" CASCADE;

CREATE TABLE "author"
(
    "id" serial NOT NULL,
    "first_name" VARCHAR(100),
    "last_name" VARCHAR(100),
    PRIMARY KEY ("id")
);

ALTER TABLE "book" ADD CONSTRAINT "book_fk_b97a1a"
    FOREIGN KEY ("author_id")
    REFERENCES "author" ("id");

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesSkipSQLDDL
     */
    public function testGetAddEntitiesDDLSkipSQL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    public function testGetAddEntitiesDDLSchemasVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="table1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </entity>
    <entity name="table2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
    <entity name="table3">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Yipee"/>
        </vendor>
    </entity>
</database>
EOF;
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

CREATE SCHEMA "Woopah";

CREATE SCHEMA "Yipee";

-----------------------------------------------------------------------
-- table1
-----------------------------------------------------------------------

SET search_path TO "Woopah";

DROP TABLE IF EXISTS "table1" CASCADE;

SET search_path TO public;

SET search_path TO "Woopah";

CREATE TABLE "table1"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

SET search_path TO public;

-----------------------------------------------------------------------
-- table2
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "table2" CASCADE;

CREATE TABLE "table2"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- table3
-----------------------------------------------------------------------

SET search_path TO "Yipee";

DROP TABLE IF EXISTS "table3" CASCADE;

SET search_path TO public;

SET search_path TO "Yipee";

CREATE TABLE "table3"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

SET search_path TO public;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDLSchema
     */
    public function testGetAddEntitiesDDLSchemas($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- x.book
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "x"."book" CASCADE;

CREATE TABLE "x"."book"
(
    "id" serial NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "author_id" INTEGER,
    PRIMARY KEY ("id")
);

CREATE INDEX "book_i_853ae9" ON "x"."book" ("title");

-----------------------------------------------------------------------
-- y.author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "y"."author" CASCADE;

CREATE TABLE "y"."author"
(
    "id" serial NOT NULL,
    "first_name" VARCHAR(100),
    "last_name" VARCHAR(100),
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- x.book_summary
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "x"."book_summary" CASCADE;

CREATE TABLE "x"."book_summary"
(
    "id" serial NOT NULL,
    "book_id" INTEGER NOT NULL,
    "summary" TEXT NOT NULL,
    PRIMARY KEY ("id")
);

ALTER TABLE "x"."book" ADD CONSTRAINT "book_fk_9f6743"
    FOREIGN KEY ("author_id")
    REFERENCES "y"."author" ("id");

ALTER TABLE "x"."book_summary" ADD CONSTRAINT "book_summary_fk_a5b8c4"
    FOREIGN KEY ("book_id")
    REFERENCES "x"."book" ("id")
    ON DELETE CASCADE;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSimplePK
     */
    public function testGetAddEntityDDLSimplePK($schema)
    {
        $table = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("id")
);

COMMENT ON TABLE "foo" IS 'This is foo table';

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLCompositePK
     */
    public function testGetAddEntityDDLCompositePK($schema)
    {
        $table = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "foo" INTEGER NOT NULL,
    "bar" INTEGER NOT NULL,
    "baz" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("foo","bar")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLUniqueIndex
     */
    public function testGetAddEntityDDLUniqueIndex($schema)
    {
        $table = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id"),
    CONSTRAINT "foo_u_853ae9" UNIQUE ("bar")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($table));
    }

    public function testGetAddEntityDDLSchemaVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </entity>
</database>
EOF;
        $table = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

SET search_path TO "Woopah";

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    PRIMARY KEY ("id")
);

SET search_path TO public;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetAddEntityDDLSchema($schema)
    {
        $table = $this->getEntityFromSchema($schema, 'Foo');
        $expected = <<<EOF

CREATE TABLE "Woopah"."foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($table));
    }

    public function testGetAddEntityDDLSequence()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <id-method-parameter value="my_custom_sequence_name"/>
    </entity>
</database>
EOF;
        $table = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE SEQUENCE "my_custom_sequence_name";

CREATE TABLE "foo"
(
    "id" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($table));
    }

    public function testGetAddEntityDDLColumnComments()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" description="identifier column"/>
        <field name="bar" type="INTEGER" description="your name here"/>
    </entity>
</database>
EOF;
        $table = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

CREATE TABLE "foo"
(
    "id" serial NOT NULL,
    "bar" INTEGER,
    PRIMARY KEY ("id")
);

COMMENT ON COLUMN "foo"."id" IS 'identifier column';

COMMENT ON COLUMN "foo"."bar" IS 'your name here';

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($table));
    }

    public function testGetDropEntityDDL()
    {
        $table = new Entity('foo');
        $expected = '
DROP TABLE IF EXISTS "foo" CASCADE;
';
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($table));
    }

    public function testGetDropEntityDDLSchemaVendor()
    {
        $schema = <<<EOF
<database name="test">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="pgsql">
            <parameter name="schema" value="Woopah"/>
        </vendor>
    </entity>
</database>
EOF;
        $table = $this->getEntityFromSchema($schema);
        $expected = <<<EOF

SET search_path TO "Woopah";

DROP TABLE IF EXISTS "foo" CASCADE;

SET search_path TO public;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($table));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetDropEntityDDLSchema($schema)
    {
        $table = $this->getEntityFromSchema($schema, 'Foo');
        $expected = <<<EOF

DROP TABLE IF EXISTS "Woopah"."foo" CASCADE;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($table));
    }

    public function testGetDropTableWithSequenceDDL()
    {
        $table = new Entity('foo');
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $table->addIdMethodParameter($idMethodParameter);
        $table->setIdMethod(Model::ID_METHOD_NATIVE);
        $expected = '
DROP TABLE IF EXISTS "foo" CASCADE;

DROP SEQUENCE "foo_sequence";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($table));
    }

    public function testGetColumnDDL()
    {
        $c = new Field('foo');
        $c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c->getDomain()->replaceScale(2);
        $c->getDomain()->setSize(3);
        $c->setNotNull(true);
        $c->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $expected = '"foo" DOUBLE PRECISION DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($c));
    }

    public function testGetColumnDDLAutoIncrement()
    {
        $database = new Database();
        $database->setPlatform($this->getPlatform());
        $table = new Entity('foo_table');
        $table->setIdMethod(Model::ID_METHOD_NATIVE);
        $database->addEntity($table);
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType(PropelTypes::BIGINT));
        $column->setAutoIncrement(true);
        $table->addField($column);
        $expected = '"foo" bigserial';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    public function testGetColumnDDLCustomSqlType()
    {
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $column->getDomain()->replaceScale(2);
        $column->getDomain()->setSize(3);
        $column->setNotNull(true);
        $column->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $column->getDomain()->replaceSqlType('DECIMAL(5,6)');
        $expected = '"foo" DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    public function testGetPrimaryKeyDDLSimpleKey()
    {
        $table = new Entity('foo');
        $column = new Field('bar');
        $column->setPrimaryKey(true);
        $table->addField($column);
        $expected = 'PRIMARY KEY ("bar")';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $table = new Entity('foo');
        $column1 = new Field('bar1');
        $column1->setPrimaryKey(true);
        $table->addField($column1);
        $column2 = new Field('bar2');
        $column2->setPrimaryKey(true);
        $table->addField($column2);
        $expected = 'PRIMARY KEY ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($table)
    {
        $expected = '
ALTER TABLE "foo" DROP CONSTRAINT "foo_pkey";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($table)
    {
        $expected = '
ALTER TABLE "foo" ADD PRIMARY KEY ("bar");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = '
CREATE INDEX "babar" ON "foo" ("bar1","bar2");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($table)
    {
        $expected = '
CREATE INDEX "babar" ON "foo" ("bar1","bar2");

CREATE INDEX "foo_index" ON "foo" ("bar1");
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($table));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = '
DROP INDEX "babar";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX "babar" ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'CONSTRAINT "babar" UNIQUE ("bar1","bar2")';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetRelationsDDL
     */
    public function testGetAddRelationsDDL($table)
    {
        $expected = '
ALTER TABLE "foo" ADD CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE;

ALTER TABLE "foo" ADD CONSTRAINT "foo_baz_fk"
    FOREIGN KEY ("baz_id")
    REFERENCES "baz" ("id")
    ON DELETE SET NULL;
';
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationsDDL($table));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetAddRelationDDL($fk)
    {
        $expected = '
ALTER TABLE "foo" ADD CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE;
';
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetAddRelationSkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetDropRelationDDL($fk)
    {
        $expected = '
ALTER TABLE "foo" DROP CONSTRAINT "foo_bar_fk";
';
        $this->assertEquals($expected, $this->getPlatform()->getDropRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetDropRelationSkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetRelationDDL($fk)
    {
        $expected = 'CONSTRAINT "foo_bar_fk"
    FOREIGN KEY ("bar_id")
    REFERENCES "bar" ("id")
    ON DELETE CASCADE';
        $this->assertEquals($expected, $this->getPlatform()->getRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetRelationSkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getRelationDDL($fk));
    }

    public function testGetCommentBlockDDL()
    {
        $expected = "
-----------------------------------------------------------------------
-- foo bar
-----------------------------------------------------------------------
";
        $this->assertEquals($expected, $this->getPlatform()->getCommentBlockDDL('foo bar'));
    }
}
