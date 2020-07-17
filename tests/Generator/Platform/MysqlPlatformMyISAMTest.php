<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\Vendor;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Schema\SchemaReader;
use Propel\Tests\VfsTrait;

/**
 *
 */
class MysqlPlatformMyISAMTest extends PlatformTestProvider
{
    use VfsTrait;

    private $platform;

    /**
     * Get the Platform object for this class
     *
     * @return MysqlPlatform
     */
    protected function getPlatform(): PlatformInterface
    {
        if (null === $this->platform) {
            $this->platform = new MysqlPlatform();
            $configFileContent = <<<EOF
propel:
  database:
    connections:
      bookstore:
        adapter: mysql
        classname: \Propel\Runtime\Connection\DebugPDO
        dsn: mysql:host=127.0.0.1;dbname=test
        user: root
        password:
    adapters:
      mysql:
        tableType: MyISAM

  generator:
    defaultConnection: bookstore
    connections:
      - bookstore

  runtime:
    defaultConnection: bookstore
    connections:
      - bookstore
EOF;

            $file = $this->newFile('propel.yaml', $configFileContent);
            $config = new GeneratorConfig($file->url());

            $this->platform->setGeneratorConfig($config);
        }

        return $this->platform;
    }

    public function testGetSequenceNameDefault()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(Model::ID_METHOD_NATIVE);
        $expected = 'foo_SEQ';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($entity));
    }

    public function testGetSequenceNameCustom()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(Model::ID_METHOD_NATIVE);
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $entity->addIdMethodParameter($idMethodParameter);
        $entity->setIdMethod(Model::ID_METHOD_NATIVE);
        $expected = 'foo_sequence';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDLSchema
     */
    public function testGetAddEntitiesDDLSchema($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until all tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- x.book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `x`.`book`;

CREATE TABLE `x`.`book`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `author_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `book_i_853ae9` (`title`),
    INDEX `book_fi_9f6743` (`author_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- y.author
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `y`.`author`;

CREATE TABLE `y`.`author`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- x.book_summary
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `x`.`book_summary`;

CREATE TABLE `x`.`book_summary`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `book_id` INTEGER NOT NULL,
    `summary` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `book_summary_fi_a5b8c4` (`book_id`)
) ENGINE=MyISAM;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDL
     */
    public function testGetAddEntitiesDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until all tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `book`;

CREATE TABLE `book`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `author_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `book_i_853ae9` (`title`),
    INDEX `book_fi_b97a1a` (`author_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- author
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `author`;

CREATE TABLE `author`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesSkipSQLDDL
     */
    public function testGetAddEntitiesSkipSQLDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSimplePK
     */
    public function testGetAddEntityDDLSimplePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM COMMENT='This is foo table';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLCompositePK
     */
    public function testGetAddEntityDDLCompositePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `foo` INTEGER NOT NULL,
    `bar` INTEGER NOT NULL,
    `baz` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`foo`,`bar`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLUniqueIndex
     */
    public function testGetAddEntityDDLUniqueIndex($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `foo_u_853ae9` (`bar`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLIndex()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <index>
            <index-field name="bar" />
        </index>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `foo_i_853ae9` (`bar`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLForeignKey()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar_id" type="INTEGER" />
        <relation target="Bar">
            <reference local="bar_id" foreign="id" />
        </relation>
    </entity>
    <entity name="Bar">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `foo_fi_64b74b` (`bar_id`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLForeignKeySkipSql()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar_id" type="INTEGER" />
        <relation target="Bar" skipSql="true">
            <reference local="bar_id" foreign="id" />
        </relation>
    </entity>
    <entity name="Bar">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `foo_fi_64b74b` (`bar_id`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLEngine()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </entity>
</database>
EOF;
        $platform = new MysqlPlatform();
        $platform->setEntityEngineKeyword('TYPE');
        $platform->setDefaultEntityEngine('MEMORY');

        $xtad = new SchemaReader();
        $appData = $xtad->parseString($schema, $platform);
        $entity = $appData->getDatabase()->getEntityByName('Foo');

        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) TYPE=MEMORY;
";
        $this->assertEquals($expected, $platform->getAddEntityDDL($entity));
    }

    public function testGetAddEntityDDLVendor()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="Foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <vendor type="mysql">
            <parameter name="Engine" value="InnoDB"/>
            <parameter name="Charset" value="utf8"/>
            <parameter name="AutoIncrement" value="1000"/>
        </vendor>
    </entity>
</database>
EOF;
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE `foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 CHARACTER SET='utf8';
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetAddEntityDDLSchema($schema)
    {
        $entity = $this->getEntityFromSchema($schema, 'Foo');
        $expected = "
CREATE TABLE `Woopah`.`foo`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `bar` INTEGER,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetDropEntityDDL()
    {
        $entity = new Entity('Foo');
        $entity->setIdentifierQuoting(true);
        $expected = "
DROP TABLE IF EXISTS `foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSchema
     */
    public function testGetDropEntityDDLSchema($schema)
    {
        $entity = $this->getEntityFromSchema($schema, 'Foo');
        $expected = "
DROP TABLE IF EXISTS `Woopah`.`foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetColumnDDL()
    {
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $column->getDomain()->replaceScale(2);
        $column->getDomain()->setSize(3);
        $column->setNotNull(true);
        $column->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $expected = '`foo` DOUBLE(3,2) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    public function testGetColumnDDLCharsetVendor()
    {
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new Vendor('mysql');
        $vendor->setParameter('Charset', 'greek');
        $column->addVendor($vendor);
        $expected = '`foo` TEXT CHARACTER SET \'greek\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    public function testGetColumnDDLCharsetCollation()
    {
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new Vendor('mysql');
        $vendor->setParameter('Collate', 'latin1_german2_ci');
        $column->addVendor($vendor);
        $expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));

        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $vendor = new Vendor('mysql');
        $vendor->setParameter('Collation', 'latin1_german2_ci');
        $column->addVendor($vendor);
        $expected = '`foo` TEXT COLLATE \'latin1_german2_ci\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    public function testGetColumnDDLComment()
    {
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('INTEGER'));
        $column->setDescription('This is column Foo');
        $expected = '`foo` INTEGER COMMENT \'This is column Foo\'';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    public function testGetColumnDDLCharsetNotNull()
    {
        $column = new Field('foo');
        $column->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $column->setNotNull(true);
        $vendor = new Vendor('mysql');
        $vendor->setParameter('Charset', 'greek');
        $column->addVendor($vendor);
        $expected = '`foo` TEXT CHARACTER SET \'greek\' NOT NULL';
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
        $expected = '`foo` DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($column));
    }

    public function testGetPrimaryKeyDDLSimpleKey()
    {
        $entity = new Entity('Foo');
        $entity->setIdentifierQuoting(true);
        $column = new Field('bar');
        $column->setPrimaryKey(true);
        $entity->addField($column);
        $expected = 'PRIMARY KEY (`bar`)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $entity = new Entity('Foo');
        $entity->setIdentifierQuoting(true);
        $column1 = new Field('bar1');
        $column1->setPrimaryKey(true);
        $entity->addField($column1);
        $column2 = new Field('bar2');
        $column2->setPrimaryKey(true);
        $entity->addField($column2);
        $expected = 'PRIMARY KEY (`bar1`,`bar2`)';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($entity)
    {
        $expected = "
ALTER TABLE `foo` DROP PRIMARY KEY;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($entity)
    {
        $expected = "
ALTER TABLE `foo` ADD PRIMARY KEY (`bar`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($entity)
    {
        $expected = "
CREATE INDEX `babar` ON `foo` (`bar1`, `bar2`);

CREATE INDEX `foo_index` ON `foo` (`bar1`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = "
CREATE INDEX `babar` ON `foo` (`bar1`, `bar2`);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = "
DROP INDEX `babar` ON `foo`;
";
        $this->assertEquals($expected, $this->getPlatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX `babar` (`bar1`, `bar2`)';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    public function testGetIndexDDLKeySize()
    {
        $entity = new Entity('Foo');
        $entity->setIdentifierQuoting(true);
        $column1 = new Field('bar1');
        $column1->getDomain()->copy($this->getPlatform()->getDomainForType('VARCHAR'));
        $column1->setSize(5);
        $entity->addField($column1);
        $index = new Index('bar_index');
        $index->addField($column1);
        $entity->addIndex($index);
        $expected = 'INDEX `bar_index` (`bar1`)';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    public function testGetIndexDDLFulltext()
    {
        $entity = new Entity('Foo');
        $entity->setIdentifierQuoting(true);
        $column1 = new Field('bar1');
        $column1->getDomain()->copy($this->getPlatform()->getDomainForType('LONGVARCHAR'));
        $entity->addField($column1);
        $index = new Index('bar_index');
        $index->addField($column1);
        $vendor = new Vendor('mysql');
        $vendor->setParameter('Index_type', 'FULLTEXT');
        $index->addVendor($vendor);
        $entity->addIndex($index);
        $expected = 'FULLTEXT INDEX `bar_index` (`bar1`)';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'UNIQUE INDEX `babar` (`bar1`, `bar2`)';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetRelationsDDL
     */
    public function testGetAddRelationsDDL($entity)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationsDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetAddRelationDDL($fk)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetAddForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetDropRelationDDL($fk)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getDropRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationSkipSqlDDL
     */
    public function testGetDropForeignKeySkipSqlDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetRelationDDL($fk)
    {
        $expected = "";
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
-- ---------------------------------------------------------------------
-- foo bar
-- ---------------------------------------------------------------------
";
        $this->assertEquals($expected, $this->getPlatform()->getCommentBlockDDL('foo bar'));
    }

    public function testAddExtraIndicesForeignKeys()
    {
        $schema = '
<database name="test1" identifierQuoting="true">
  <entity name="Foo">
    <behavior name="AutoAddPk"/>
    <field name="name" type="VARCHAR"/>
    <field name="subid" type="INTEGER"/>
  </entity>
  <entity name="Bar">
    <behavior name="AutoAddPk"/>

    <field name="name" type="VARCHAR"/>
    <field name="subid" type="INTEGER"/>

    <relation target="Foo">
      <reference local="id" foreign="id"/>
      <reference local="subid" foreign="subid"/>
    </relation>
  </entity>
</database>
';

        $expectedRelationSql = "
CREATE TABLE `bar`
(
    `name` VARCHAR,
    `subid` INTEGER,
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`),
    INDEX `bar_fi_9620dc` (`id`, `subid`)
) ENGINE=MyISAM;
";

        $entity = $this->getDatabaseFromSchema($schema)->getEntityByName('Bar');
        $relationTableSql = $this->getPlatform()->getAddEntityDDL($entity);

        $this->assertEquals($expectedRelationSql, $relationTableSql);
    }
}
