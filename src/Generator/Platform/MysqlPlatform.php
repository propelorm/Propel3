<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Platform;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\NamingTool;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Generator\Model\Vendor;

/**
 * MySql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class MysqlPlatform extends SqlDefaultPlatform
{
    protected $tableEngineKeyword = 'ENGINE';  // overwritten in propel config
    protected $defaultEntityEngine = 'InnoDB';  // overwritten in propel config

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, 'TINYINT', 1));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'DECIMAL'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONGBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'LONGTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'ENUM'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, 'DOUBLE'));
    }

    /**
     * Adds extra indices for reverse foreign keys
     * This is required for MySQL databases,
     * and is called while performing the final initialization of the model.
     *
     * @param Entity $entity
     */
    protected function addExtraIndices(Entity $entity): void
    {
        /**
         * A collection of indexed columns. The keys is the column name
         * (concatenated with a comma in the case of multi-col index), the value is
         * an array with the names of the indexes that index these columns. We use
         * it to determine which additional indexes must be created for foreign
         * keys. It could also be used to detect duplicate indexes, but this is not
         * implemented yet.
         * @var array
         */
        $indices = [];

        $this->collectIndexedFields('PRIMARY', $entity->getPrimaryKey(), $indices);

        /** @var Index[] $entityIndices */
        $entityIndices = array_merge($entity->getIndices()->toArray(), $entity->getUnices()->toArray());
        foreach ($entityIndices as $index) {
            $this->collectIndexedFields($this->getName($index), $index->getFields()->toArray(), $indices);
        }

        // we're determining which entitys have foreign keys that point to this entity,
        // since MySQL needs an index on any column that is referenced by another entity
        $counter = 0;
        foreach ($entity->getReferrers() as $relation) {
            $referencedFields = $relation->getForeignFieldObjects();
            $referencedFieldsHash = $this->getFieldList($referencedFields->toArray());
            if (empty($referencedFields) || isset($indices[$referencedFieldsHash])) {
                continue;
            }

            // no matching index defined in the schema, so we have to create one
            $name = sprintf('i_referenced_%s_%s', $this->getName($relation), ++$counter);
            if ($entity->hasIndex($name)) {
                // if we have already a index with this name, then it looks like the columns of this index have just
                // been changed, so remove it and inject it again. This is the case if a referenced entity is handled
                // later than the referencing entity.
                $entity->removeIndex($name);
            }

            $index = $entity->createIndex($name, $referencedFields);
            // Add this new index to our collection, otherwise we might add it again (bug #725)
            $this->collectIndexedFields($this->getName($index), $referencedFields, $indices);
        }

        // we're adding indices for this entity foreign keys
        /** @var Relation $relation */
        foreach ($entity->getRelations() as $relation) {
            $localFields = $relation->getLocalFieldObjects();
            $localFieldsHash = $this->getFieldList($localFields->toArray());
            if (empty($localFields) || isset($indices[$localFieldsHash])) {
                continue;
            }

            // No matching index defined in the schema, so we have to create one.
            // MySQL needs indices on any columns that serve as foreign keys.
            // These are not auto-created prior to 4.1.2.

            $name = substr_replace($relation->getName()->toString(), 'fi_', strrpos($relation->getName()->toString(), 'fk_'), 3);
            if ($entity->hasIndex($name)) {
                // if we already have an index with this name, then it looks like the columns of this index have just
                // been changed, so remove it and inject it again. This is the case if a referenced entity is handled
                // later than the referencing entity.
                $entity->removeIndex($name);
            }

            $index = $entity->createIndex($name, $localFields->toArray());
            $this->collectIndexedFields($this->getName($index), $localFields->toArray(), $indices);
        }
    }

    /**
     * Helper function to collect indexed columns.
     *
     * @param string $indexName        The name of the index
     * @param array  $columns          The column names or objects
     * @param array  $collectedIndexes The collected indexes
     */
    protected function collectIndexedFields(string $indexName, array $columns, array &$collectedIndexes)
    {
        /**
         * "If the entity has a multiple-column index, any leftmost prefix of the
         * index can be used by the optimizer to find rows. For example, if you
         * have a three-column index on (col1, col2, col3), you have indexed search
         * capabilities on (col1), (col1, col2), and (col1, col2, col3)."
         * @link http://dev.mysql.com/doc/refman/5.5/en/mysql-indexes.html
         */
        $indexedFields = [];
        foreach ($columns as $column) {
            $indexedFields[] = $column;
            $indexedFieldsHash = $this->getFieldList($indexedFields);
            if (!isset($collectedIndexes[$indexedFieldsHash])) {
                $collectedIndexes[$indexedFieldsHash] = [];
            }
            $collectedIndexes[$indexedFieldsHash][] = $indexName;
        }
    }


    /**
     * Returns a delimiter-delimited string list of column names.
     *
     * @see Platform::getFieldList() if quoting is required
     * @param array
     * @param  string $delimiter
     * @return string
     */
    public function getFieldList(array $columns, string $delimiter = ','): string
    {
        $list = [];
        foreach ($columns as $col) {
            if ($col instanceof Field) {
                $col = $this->getName($col);
            }
            $list[] = $col;
        }

        return implode($delimiter, $list);
    }

    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        parent::setGeneratorConfig($generatorConfig);

        if ($defaultEntityEngine = $generatorConfig->get()['database']['adapters']['mysql']['tableType']) {
            $this->defaultEntityEngine = $defaultEntityEngine;
        }
        if ($tableEngineKeyword = $generatorConfig->get()['database']['adapters']['mysql']['tableEngineKeyword']) {
            $this->tableEngineKeyword = $tableEngineKeyword;
        }
    }

    /**
     * Setter for the tableEngineKeyword property
     *
     * @param string $tableEngineKeyword
     */
    public function setEntityEngineKeyword(string $tableEngineKeyword)
    {
        $this->tableEngineKeyword = $tableEngineKeyword;
    }

    /**
     * Getter for the tableEngineKeyword property
     *
     * @return string
     */
    public function getEntityEngineKeyword(): string
    {
        return $this->tableEngineKeyword;
    }

    /**
     * Setter for the defaultEntityEngine property
     *
     * @param string $defaultEntityEngine
     */
    public function setDefaultEntityEngine(string $defaultEntityEngine)
    {
        $this->defaultEntityEngine = $defaultEntityEngine;
    }

    /**
     * Getter for the defaultEntityEngine property
     *
     * @return string
     */
    public function getDefaultEntityEngine(): string
    {
        return $this->defaultEntityEngine;
    }

    public function getAutoIncrement(): string
    {
        return 'AUTO_INCREMENT';
    }

    public function getMaxFieldNameLength(): int
    {
        return 64;
    }

    public function supportsNativeDeleteTrigger(): bool
    {
        return strtolower($this->getDefaultEntityEngine()) == 'innodb';
    }

    public function supportsIndexSize(): bool
    {
        return true;
    }

    public function supportsRelations(Entity $entity): bool
    {
        $vendorSpecific = $entity->getVendorByType('mysql');

        if ($vendorSpecific->hasParameter('Type')) {
            $mysqlEntityType = $vendorSpecific->getParameter('Type');
        } elseif ($vendorSpecific->hasParameter('Engine')) {
            $mysqlEntityType = $vendorSpecific->getParameter('Engine');
        } else {
            $mysqlEntityType = $this->getDefaultEntityEngine();
        }

        return strtolower($mysqlEntityType) == 'innodb';
    }

    public function getAddEntitiesDDL(Database $database): string
    {
        $ret = '';
        foreach ($database->getEntitiesForSql() as $entity) {
            $ret .= $this->getCommentBlockDDL($this->getName($entity));
            $ret .= $this->getDropEntityDDL($entity);
            $ret .= $this->getAddEntityDDL($entity);
        }
        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    public function getBeginDDL(): string
    {
        return "
# This is a fix for InnoDB in MySQL >= 4.1.x
# It \"suspends judgement\" for fkey relationships until all tables are set.
SET FOREIGN_KEY_CHECKS = 0;
";
    }

    public function getEndDDL(): string
    {
        return "
# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
";
    }

    /**
     * Returns the SQL for the primary key of a Entity object
     *
     * @param Entity $entity
     *
     * @return string
     */
    public function getPrimaryKeyDDL(Entity $entity): string
    {
        if ($entity->hasPrimaryKey()) {
            $keys = $entity->getPrimaryKey();

            //MySQL throws an 'Incorrect entity definition; there can be only one auto field and it must be defined as a key'
            //if the primary key consists of multiple fields and if the first is not the autoIncrement one. So
            //this pushes the autoIncrement field to the first position if its not already.
            $autoIncrement = $entity->getAutoIncrementPrimaryKey();
            if ($autoIncrement && $keys[0] != $autoIncrement) {
                $idx = array_search($autoIncrement, $keys);
                if ($idx !== false) {
                    unset($keys[$idx]);
                    array_unshift($keys, $autoIncrement);
                }
            }

            return 'PRIMARY KEY (' . $this->getFieldListDDL($keys) . ')';
        }
    }

    public function getAddEntityDDL(Entity $entity): string
    {
        $lines = [];

        foreach ($entity->getFields() as $field) {
            $lines[] = $this->getFieldDDL($field);
        }

        if ($entity->hasPrimaryKey()) {
            $lines[] = $this->getPrimaryKeyDDL($entity);
        }

        foreach ($entity->getUnices() as $unique) {
            $lines[] = $this->getUniqueDDL($unique);
        }

        foreach ($entity->getIndices() as $index) {
            $lines[] = $this->getIndexDDL($index);
        }

        if ($this->supportsRelations($entity)) {
            foreach ($entity->getRelations() as $relation) {
                if ($relation->isSkipSql()) {
                    continue;
                }
                $lines[] = str_replace("
    ", "
        ", $this->getRelationDDL($relation));
            }
        }

        $mysqlEntityType = $this->getMysqlEntityType($entity);

        $entityOptions = $this->getEntityOptions($entity);

        if ($entity->getDescription()) {
            $entityOptions[] = 'COMMENT=' . $this->quote($entity->getDescription());
        }

        $entityOptions = $entityOptions ? ' ' . implode(' ', $entityOptions) : '';
        $sep = ",
    ";

        $pattern = "
CREATE TABLE %s
(
    %s
) %s=%s%s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($entity)),
            implode($sep, $lines),
            $this->getEntityEngineKeyword(),
            $mysqlEntityType,
            $entityOptions
        );
    }

    protected function getEntityOptions(Entity $entity)
    {
        $dbVI = $entity->getDatabase()->getVendorByType('mysql');
        $entityVI = $entity->getVendorByType('mysql');
        $vi = $dbVI->getMergedVendorInfo($entityVI);
        $entityOptions = [];
        // List of supported entity options
        // see http://dev.mysql.com/doc/refman/5.5/en/create-entity.html
        $supportedOptions = [
            'AutoIncrement'   => 'AUTO_INCREMENT',
            'AvgRowLength'    => 'AVG_ROW_LENGTH',
            'Charset'         => 'CHARACTER SET',
            'Checksum'        => 'CHECKSUM',
            'Collate'         => 'COLLATE',
            'Connection'      => 'CONNECTION',
            'DataDirectory'   => 'DATA DIRECTORY',
            'Delay_key_write' => 'DELAY_KEY_WRITE',
            'DelayKeyWrite'   => 'DELAY_KEY_WRITE',
            'IndexDirectory'  => 'INDEX DIRECTORY',
            'InsertMethod'    => 'INSERT_METHOD',
            'KeyBlockSize'    => 'KEY_BLOCK_SIZE',
            'MaxRows'         => 'MAX_ROWS',
            'MinRows'         => 'MIN_ROWS',
            'Pack_Keys'       => 'PACK_KEYS',
            'PackKeys'        => 'PACK_KEYS',
            'RowFormat'       => 'ROW_FORMAT',
            'Union'           => 'UNION',
        ];

        $noQuotedValue = array_flip([
            'InsertMethod',
            'Pack_Keys',
            'PackKeys',
            'RowFormat',
        ]);

        foreach ($supportedOptions as $name => $sqlName) {
            $parameterValue = null;

            if ($vi->hasParameter($name)) {
                $parameterValue = $vi->getParameter($name);
            } elseif ($vi->hasParameter($sqlName)) {
                $parameterValue = $vi->getParameter($sqlName);
            }

            // if we have a param value, then parse it out
            if (!is_null($parameterValue)) {
                // if the value is numeric or is parameter is in $noQuotedValue, then there is no need for quotes
                if (!is_numeric($parameterValue) && !isset($noQuotedValue[$name])) {
                    $parameterValue = $this->quote($parameterValue);
                }

                $entityOptions [] = sprintf('%s=%s', $sqlName, $parameterValue);
            }
        }

        return $entityOptions;
    }

    public function getDropEntityDDL(Entity $entity): string
    {
        return "
DROP TABLE IF EXISTS " . $this->quoteIdentifier($this->getName($entity)) . ";
";
    }

    public function getFieldDDL(Field $col): string
    {
        $domain = $col->getDomain();
        if (!$col->isDefaultSqlType($this) && !$domain->isReplaced()) {
            $domain = $this->getDomainForType($col->getType());
        }
        $sqlType = $domain->getSqlType();
        $notNullString = $col->isNotNull() ? $this->getNotNullString() : '';
        $defaultSetting = $this->getFieldDefaultValueDDL($col);

        // Special handling of TIMESTAMP/DATETIME types ...
        // See: http://propel.phpdb.org/trac/ticket/538
        if ($sqlType === 'DATETIME') {
            $def = $domain->getDefaultValue();
            if ($def && $def->isExpression()) {
                // DATETIME values can only have constant expressions
                $sqlType = 'TIMESTAMP';
            }
        } elseif ($sqlType === 'DATE') {
            $def = $domain->getDefaultValue();
            if ($def && $def->isExpression()) {
                throw new EngineException('DATE fields cannot have default *expressions* in MySQL.');
            }
        } elseif ($sqlType === 'TEXT' || $sqlType === 'BLOB') {
            if ($domain->getDefaultValue()) {
                throw new EngineException('BLOB and TEXT fields cannot have DEFAULT values. in MySQL.');
            }
        }

        $ddl = [$this->quoteIdentifier($this->getName($col))];
        if ($this->hasSize($sqlType) && !$col->getDomain()->isReplaced()) {
            $ddl[] = $sqlType . ($col->getSizeDefinition() !== '' ? $col->getSizeDefinition() : $this->getDomainForType($col->getType())->getSizeDefinition());
        } else {
            $ddl[] = $sqlType;
        }

        if ($sqlType == "ENUM") {
            $ddl[] = '("' . implode($col->getValueSet()->toArray(), '","') . '")';
        }

        $colinfo = $col->getVendorByType($this->getDatabaseType());
        if ($colinfo->hasParameter('Charset')) {
            $ddl[] = 'CHARACTER SET ' . $this->quote($colinfo->getParameter('Charset'));
        }
        if ($colinfo->hasParameter('Collation')) {
            $ddl[] = 'COLLATE ' . $this->quote($colinfo->getParameter('Collation'));
        } elseif ($colinfo->hasParameter('Collate')) {
            $ddl[] = 'COLLATE ' . $this->quote($colinfo->getParameter('Collate'));
        }

        if ($sqlType === 'TIMESTAMP') {
            if ($notNullString == '') {
                $notNullString = 'NULL';
            }
            if ($defaultSetting == '' && $notNullString === 'NOT NULL') {
                $defaultSetting = 'DEFAULT CURRENT_TIMESTAMP';
            }
            if ($notNullString) {
                $ddl[] = $notNullString;
            }
            if ($defaultSetting) {
                $ddl[] = $defaultSetting;
            }
        } else {
            if ($defaultSetting) {
                $ddl[] = $defaultSetting;
            }
            if ($notNullString) {
                $ddl[] = $notNullString;
            }
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }
        if ($col->getDescription()) {
            $ddl[] = 'COMMENT ' . $this->quote($col->getDescription());
        }

        return implode(' ', $ddl);
    }

    /**
     * Creates a comma-separated list of field names for the index.
     * For MySQL unique indexes there is the option of specifying size, so we cannot simply use
     * the getFieldsList() method.
     * @param  Index  $index
     * @return string
     */
    protected function getIndexFieldListDDL(Index $index)
    {
        $list = [];
        foreach ($index->getFields() as $col) {
            $element = $this->quoteIdentifier($this->getName($col));
            $list[] = $element .
                ($index->getFieldSizes()->get($col->getName()) ? "({$index->getFieldSizes()->get($col->getName())})" : '');
        }

        return implode(', ', $list);
    }

    /**
     * Builds the DDL SQL to drop the primary key of a entity.
     *
     * @param  Entity  $entity
     * @return string
     */
    public function getDropPrimaryKeyDDL(Entity $entity): string
    {
        if (!$entity->hasPrimaryKey()) {
            return '';
        }

        $pattern = "
ALTER TABLE %s DROP PRIMARY KEY;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($entity))
        );
    }

    /**
     * Builds the DDL SQL to add an Index.
     *
     * @param  Index  $index
     * @return string
     */
    public function getAddIndexDDL(Index $index): string
    {
        $pattern = "
CREATE %sINDEX %s ON %s (%s);
";

        return sprintf(
            $pattern,
            $this->getIndexType($index),
            $this->quoteIdentifier($this->getName($index)),
            $this->quoteIdentifier($this->getName($index->getEntity())),
            $this->getIndexFieldListDDL($index)
        );
    }

    /**
     * Builds the DDL SQL to drop an Index.
     *
     * @param  Index  $index
     * @return string
     */
    public function getDropIndexDDL(Index $index): string
    {
        $pattern = "
DROP INDEX %s ON %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($index)),
            $this->quoteIdentifier($this->getName($index->getEntity()))
        );
    }

    /**
     * Builds the DDL SQL for an Index object.
     * @return string
     */
    public function getIndexDDL(Index $index): string
    {
        return sprintf(
            '%sINDEX %s (%s)',
            $this->getIndexType($index),
            $this->quoteIdentifier($this->getName($index)),
            $this->getIndexFieldListDDL($index)
        );
    }

    protected function getIndexType(Index $index)
    {
        $type = '';
        $vendorInfo = $index->getVendorByType($this->getDatabaseType());
        if ($vendorInfo && $vendorInfo->getParameter('Index_type')) {
            $type = $vendorInfo->getParameter('Index_type') . ' ';
        } elseif ($index->isUnique()) {
            $type = 'UNIQUE ';
        }

        return $type;
    }

    public function getUniqueDDL(Unique $unique): string
    {
        return sprintf(
            'UNIQUE INDEX %s (%s)',
            $this->quoteIdentifier($this->getName($unique)),
            $this->getIndexFieldListDDL($unique)
        );
    }

    public function getAddRelationDDL(Relation $relation): string
    {
        if ($this->supportsRelations($relation->getEntity())) {
            return parent::getAddRelationDDL($relation);
        }

        return '';
    }

    /**
     * Builds the DDL SQL for a Relation object.
     *
     * @param Relation $relation
     *
     * @return string
     */
    public function getRelationDDL(Relation $relation): string
    {
        if ($this->supportsRelations($relation->getEntity())) {
            return parent::getRelationDDL($relation);
        }

        return '';
    }

    public function getDropRelationDDL(Relation $relation): string
    {
        if (!$this->supportsRelations($relation->getEntity())) {
            return '';
        }

        if ($relation->isSkipSql()) {
            return '';
        }
        $pattern = "
ALTER TABLE %s DROP FOREIGN KEY %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($relation->getEntity())),
            $this->quoteIdentifier($this->getName($relation))
        );
    }

    public function getCommentBlockDDL(string $comment): string
    {
        $pattern = "
-- ---------------------------------------------------------------------
-- %s
-- ---------------------------------------------------------------------
";

        return sprintf($pattern, $comment);
    }

    /**
     * Builds the DDL SQL to modify a database
     * based on a DatabaseDiff instance
     *
     * @return string
     */
    public function getModifyDatabaseDDL(DatabaseDiff $databaseDiff): string
    {
        $ret = '';

        foreach ($databaseDiff->getRemovedEntities() as $entity) {
            $ret .= $this->getDropEntityDDL($entity);
        }

        foreach ($databaseDiff->getRenamedEntities() as $fromEntityName => $toEntityName) {
            $ret .= $this->getRenameEntityDDL($fromEntityName, $toEntityName);
        }

        foreach ($databaseDiff->getModifiedEntities() as $entityDiff) {
            $ret .= $this->getModifyEntityDDL($entityDiff);
        }

        foreach ($databaseDiff->getAddedEntities() as $entity) {
            $ret .= $this->getAddEntityDDL($entity);
        }

        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to rename a entity
     * @return string
     */
    public function getRenameEntityDDL(string $fromEntityName, string $toEntityName): string
    {
        $pattern = "
RENAME TABLE %s TO %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier(NamingTool::toSnakeCase($fromEntityName)),
            $this->quoteIdentifier(NamingTool::toSnakeCase($toEntityName))
        );
    }

    /**
     * Builds the DDL SQL to remove a field
     *
     * @return string
     */
    public function getRemoveFieldDDL(Field $field): string
    {
        $pattern = "
ALTER TABLE %s DROP %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($field->getEntity())),
            $this->quoteIdentifier($this->getName($field))
        );
    }

    /**
     * Builds the DDL SQL to rename a field
     * @return string
     */
    public function getRenameFieldDDL(Field $fromField, Field $toField): string
    {
        return $this->getChangeFieldDDL($fromField, $toField);
    }

    /**
     * Builds the DDL SQL to modify a field
     *
     * @return string
     */
    public function getModifyFieldDDL(FieldDiff $fieldDiff): string
    {
        return $this->getChangeFieldDDL($fieldDiff->getFromField(), $fieldDiff->getToField());
    }

    /**
     * Builds the DDL SQL to change a field
     * @return string
     */
    public function getChangeFieldDDL(Field $fromField, Field $toField): string
    {
        $pattern = "
ALTER TABLE %s CHANGE %s %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($fromField->getEntity())),
            $this->quoteIdentifier($this->getName($fromField)),
            $this->getFieldDDL($toField)
        );
    }
    /**
     * Builds the DDL SQL to modify a list of fields
     *
     * @return string
     */
    public function getModifyFieldsDDL($fieldDiffs): string
    {
        $ret = '';
        foreach ($fieldDiffs as $fieldDiff) {
            $ret .= $this->getModifyFieldDDL($fieldDiff);
        }

        return $ret;
    }

    /**
     * @see Platform::supportsSchemas()
     */
    public function supportsSchemas(): bool
    {
        return true;
    }

    public function hasSize(string $sqlType): bool
    {
        return !in_array($sqlType, [
            'MEDIUMTEXT',
            'LONGTEXT',
            'BLOB',
            'MEDIUMBLOB',
            'LONGBLOB',
        ]);
    }

    public function getDefaultTypeSizes(): array
    {
        return [
            'char'     => 1,
            'tinyint'  => 4,
            'smallint' => 6,
            'int'      => 11,
            'bigint'   => 20,
            'decimal'  => 10,
        ];
    }

    /**
     * Escape the string for RDBMS.
     * @param  string $text
     * @return string
     */
    public function disconnectedEscapeText(string $text): string
    {
        return addslashes($text);
    }

    /**
     * {@inheritdoc}
     *
     * MySQL documentation says that identifiers cannot contain '.'. Thus it
     * should be safe to split the string by '.' and quote each part individually
     * to allow for a <schema>.<entity> or <entity>.<field> syntax.
     *
     * @param  string $text the identifier
     * @return string the quoted identifier
     */
    public function doQuoting(string $text): string
    {
        return '`' . strtr($text, ['.' => '`.`']) . '`';
    }

    public function getTimestampFormatter(): string
    {
        return 'Y-m-d H:i:s';
    }

    public function getMysqlEntityType(Entity $entity): string
    {
        $vendorSpecific = $entity->getVendorByType('mysql');
        if ($vendorSpecific->getParameters()->isEmpty()) {
            $vendorSpecific = $entity->getDatabase()->getVendorByType('mysql');
        }

        if ($vendorSpecific->hasParameter('Type')) {
            return $vendorSpecific->getParameter('Type');
        }
        if ($vendorSpecific->hasParameter('Engine')) {
            return $vendorSpecific->getParameter('Engine');
        }

        return $this->getDefaultEntityEngine();
    }

    /*
    public function getFieldBindingPHP(Field $field, $identifier, $fieldValueAccessor, $tab = "            ")
    {
        // FIXME - This is a temporary hack to get around apparent bugs w/ PDO+MYSQL
        // See http://pecl.php.net/bugs/bug.php?id=9919
        if ($field->getPDOType() === \PDO::PARAM_BOOL) {
            return sprintf(
                "
%s\$stmt->bindValue(%s, (int) %s, PDO::PARAM_INT);",
                $tab,
                $identifier,
                $fieldValueAccessor
            );
        }

        return parent::getFieldBindingPHP($field, $identifier, $fieldValueAccessor, $tab);
    }
    */
}
