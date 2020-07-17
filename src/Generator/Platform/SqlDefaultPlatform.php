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

use Propel\Common\Collection\Map;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\NamingTool;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Platform\Builder\Repository;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Default implementation for the PlatformInterface interface.
 *
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class SqlDefaultPlatform implements PlatformInterface
{
    use FinalizationTrait;

    /**
     * Mapping from Propel types to Domain objects.
     *
     * @var array
     */
    protected $schemaDomainMap;

    /**
     * The database connection.
     *
     * @var ConnectionInterface Database connection.
     */
    protected $con;

    /**
     * @var bool
     */
    protected $identifierQuoting = true;

    /**
     * Default constructor.
     *
     * @param ConnectionInterface $con Optional database connection to use in this platform.
     */
    public function __construct(ConnectionInterface $con = null)
    {
        if (null !== $con) {
            $this->setConnection($con);
        }

        $this->initialize();
    }

    /**
     * @param $object
     *
     * @return string
     */
    public function getName($object): string
    {
        if ($object instanceof Entity) {
            return $object->getFullTableName()->toString();
        } elseif ($object instanceof Field) {
            return $object->getColumnName()->toString();
        } else {
            return $object->getName()->toSnakeCase()->toString();
        }
    }

    public function getRepositoryBuilder(Entity $entity): Repository
    {
        return new Repository($entity);
    }

    /**
     * Sets the database connection to use for this Platform class.
     *
     * @param ConnectionInterface $con Database connection to use in this platform.
     */
    public function setConnection(ConnectionInterface $con = null)
    {
        $this->con = $con;
    }

    /**
     * Returns the database connection to use for this Platform class.
     *
     * @return ConnectionInterface
     */
    public function getConnection(): ?ConnectionInterface
    {
        return $this->con;
    }

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        return $this->identifierQuoting;
    }

    /**
     * @param boolean $enabled
     */
    public function setIdentifierQuoting(bool $enabled)
    {
        $this->identifierQuoting = $enabled;
    }

    /**
     * Sets the GeneratorConfigInterface to use in the parsing.
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
    }

    /**
     * Initialize the type -> Domain mapping.
     */
    protected function initialize()
    {
        $this->schemaDomainMap = [];
        foreach (PropelTypes::getPropelTypes() as $type) {
            $this->schemaDomainMap[$type] = new Domain($type);
        }
        // BU_* no longer needed, so map these to the DATE/TIMESTAMP domains
        $this->schemaDomainMap[PropelTypes::BU_DATE] = new Domain(PropelTypes::DATE);
        $this->schemaDomainMap[PropelTypes::BU_TIMESTAMP] = new Domain(PropelTypes::TIMESTAMP);

        // Boolean is a bit special, since typically it must be mapped to INT type.
        $this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain(PropelTypes::BOOLEAN, 'INTEGER');
    }

    /**
     * Adds a mapping entry for specified Domain.
     * @param Domain $domain
     */
    protected function setSchemaDomainMapping(Domain $domain)
    {
        $this->schemaDomainMap[$domain->getType()] = $domain;
    }

    /**
     * Returns the short name of the database type that this platform represents.
     * For example MysqlPlatform->getDatabaseType() returns 'mysql'.
     *
     * @return string
     * @throws \ReflectionException
     */
    public function getDatabaseType(): string
    {
        $reflClass = new \ReflectionClass($this);
        $clazz = $reflClass->getShortName();
        $pos = strpos($clazz, 'Platform');

        return strtolower(substr($clazz, 0, $pos));
    }

    /**
     * Returns the max field length supported by the db.
     *
     * @return int The max field length
     */
    public function getMaxFieldNameLength(): int
    {
        return 64;
    }

    /**
     * @return string
     */
    public function getSchemaDelimiter(): string
    {
        return $this->supportsSchemas() ? '.' : '';
    }

    /**
     * Returns the native IdMethod (sequence|identity)
     *
     * @return string The native IdMethod (PlatformInterface:IDENTITY, PlatformInterface::SEQUENCE).
     */
    public function getNativeIdMethod(): string
    {
        return PlatformInterface::IDENTITY;
    }

    public function isNativeIdMethodAutoIncrement(): bool
    {
        return PlatformInterface::IDENTITY === $this->getNativeIdMethod();
    }

    /**
     * Returns the database specific domain for a mapping type.
     *
     * @param string
     * @return Domain
     */
    public function getDomainForType(string $mappingType): Domain
    {
        if (!isset($this->schemaDomainMap[$mappingType])) {
            throw new EngineException(sprintf('Cannot map unknown Propel type %s to native database type.', var_export($mappingType, true)));
        }

        return $this->schemaDomainMap[$mappingType];
    }

    /**
     * Returns the NOT NULL string for the configured RDBMS.
     *
     * @return string.
     */
    public function getNullString(): string
    {
        return '';
    }

    public function getNotNullString(): string
    {
        return 'NOT NULL';
    }

    /**
     * Returns the auto increment strategy for the configured RDBMS.
     *
     * @return string.
     */
    public function getAutoIncrement(): string
    {
        return 'IDENTITY';
    }

    /**
     * Returns the name to use for creating a entity sequence.
     *
     * This will create a new name or use one specified in an
     * id-method-parameter tag, if specified.
     *
     * @param Entity $entity
     * @return string
     */
    public function getSequenceName(Entity $entity): string
    {
        static $longNamesMap = [];
        $result = null;
        if (Model::ID_METHOD_NATIVE === $entity->getIdMethod()) {
            $idMethodParams = $entity->getIdMethodParameters();
            $maxIdentifierLength = $this->getMaxFieldNameLength();
            $entityName = $entity->getTableName();
            if (empty($idMethodParams)) {
                if (strlen($entityName . '_SEQ') > $maxIdentifierLength) {
                    if (!isset($longNamesMap[$entity->getName()])) {
                        $longNamesMap[$entityName] = strval(count($longNamesMap) + 1);
                    }
                    $result = substr($entityName, 0, $maxIdentifierLength - strlen('_SEQ_' . $longNamesMap[$entityName])) . '_SEQ_' . $longNamesMap[$entityName];
                } else {
                    $result = substr($entityName, 0, $maxIdentifierLength -4) . '_SEQ';
                }
            } else {
                $result = substr($idMethodParams[0]->getValue(), 0, $maxIdentifierLength);
            }
        }

        return $result;
    }

    /**
     * Returns the DDL SQL to add the entitys of a database
     * together with index and foreign keys
     *
     * @param Database $database
     * @return string
     */
    public function getAddEntitiesDDL(Database $database): string
    {
        $ret = $this->getBeginDDL();
        foreach ($database->getEntitiesForSql() as $entity) {
            $this->normalizeEntity($entity);
        }
        foreach ($database->getEntitiesForSql() as $entity) {
            $ret .= $this->getCommentBlockDDL($entity->getFullTableName());
            $ret .= $this->getDropEntityDDL($entity);
            $ret .= $this->getAddEntityDDL($entity);
            $ret .= $this->getAddIndicesDDL($entity);
            $ret .= $this->getAddRelationsDDL($entity);
        }
        $ret .= $this->getEndDDL();

        return $ret;
    }

    /**
     * Gets the requests to execute at the beginning of a DDL file
     *
     * @return string
     */
    public function getBeginDDL(): string
    {
        return '';
    }

    /**
     * Gets the requests to execute at the end of a DDL file
     *
     * @return string
     */
    public function getEndDDL(): string
    {
        return '';
    }

    /**
     * Builds the DDL SQL to drop a entity
     *
     * @param Entity $entity
     * @return string
     */
    public function getDropEntityDDL(Entity $entity): string
    {
        return "
DROP TABLE IF EXISTS " . $this->quoteIdentifier($this->getName($entity)) . ";
";
    }

    /**
     * Builds the DDL SQL to add a entity
     * without index and foreign keys
     *
     * @param Entity $entity
     * @return string
     */
    public function getAddEntityDDL(Entity $entity): string
    {
        $entityDescription = $entity->hasDescription() ? $this->getCommentLineDDL($entity->getDescription()) : '';

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

        $sep = ",
    ";

        $pattern = "
%sCREATE TABLE %s
(
    %s
);
";

        return sprintf(
            $pattern,
            $entityDescription,
            $this->quoteIdentifier($this->getName($entity)),
            implode($sep, $lines)
        );
    }

    /**
     * Builds the DDL SQL for a Field object.
     *
     * @param Field $col
     * @return string
     */
    public function getFieldDDL(Field $col): string
    {
        $ddl = [$this->quoteIdentifier($this->getName($col))];
        $sqlType = $col->getDomain()->getSqlType();
        if (!$col->isDefaultSqlType($this) && !$col->getDomain()->isReplaced()) {
            $sqlType = $this->getDomainForType($col->getType())->getSqlType();
        }
        if ($this->hasSize($sqlType) && !$col->getDomain()->isReplaced()) {
            $ddl[] = $sqlType . (
                $col->getSizeDefinition() !== '' ?
                    $col->getSizeDefinition() :
                    $this->getDomainForType($col->getType())->getSizeDefinition()
                );
        } else {
            $ddl[] = $sqlType;
        }
        if ($default = $this->getFieldDefaultValueDDL($col)) {
            $ddl[] = $default;
        }
        if ($col->isNotNull()) {
            $ddl[] = $this->getNotNullString();
        } elseif ('' !== $nullString = $this->getNullString()) {
            $ddl[] = $this->getNullString();
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }

        return implode(' ', $ddl);
    }

    /**
     * Returns the SQL for the default value of a Field object
     *
     * @param Field $col
     * @return string
     */
    public function getFieldDefaultValueDDL(Field $col): string
    {
        $default = '';
        $defaultValue = $col->getDefaultValue();
        if (null !== $defaultValue) {
            $default .= 'DEFAULT ';
            if ($defaultValue->isExpression()) {
                $default .= $defaultValue->getValue();
            } else {
                if ($col->isTextType()) {
                    $default .= $this->quote((string) $defaultValue->getValue());
                } elseif (in_array($col->getType(), [PropelTypes::BOOLEAN, PropelTypes::BOOLEAN_EMU])) {
                    $default .= $this->getBooleanString($defaultValue->getValue());
                } elseif ($col->getType() == PropelTypes::ENUM) {
                    $default .= $this->quote($defaultValue->getValue());
                } elseif ($col->isPhpArrayType()) {
                    $value = $this->getPhpArrayString($defaultValue->getValue());
                    if (null === $value) {
                        $default = '';
                    } else {
                        $default .= $value;
                    }
                } else {
                    $default .= $defaultValue->getValue();
                }
            }
        }

        return $default;
    }

    /**
     * Creates a delimiter-delimited string list of field names, quoted using quoteIdentifier().
     * @example
     * <code>
     * echo $platform->getFieldListDDL(array('foo', 'bar');
     * // '"foo","bar"'
     * </code>
     * @param array Field[] or string[]
     * @param string $delimiter The delimiter to use in separating the field names.
     *
     * @return string
     */
    public function getFieldListDDL($fields, string $delimiter = ','): string
    {
        $list = [];
        if (!$fields) {
            throw new BuildException('Can not generate a field list DDL without fields.');
        }
        foreach ($fields as $field) {
            $fieldName = $this->getName($field);
            $list[] = $this->quoteIdentifier($fieldName);
        }

        return implode($delimiter, $list);
    }

    /**
     * Returns the name of a entity primary key.
     *
     * @param Entity $entity
     * @return string
     */
    public function getPrimaryKeyName(Entity $entity): string
    {
        return $entity->getFullTableName() . '_pk';
    }

    /**
     * Returns the SQL for the primary key of a Entity object.
     *
     * @param Entity $entity
     * @return string
     */
    public function getPrimaryKeyDDL(Entity $entity): string
    {
        if ($entity->hasPrimaryKey()) {
            return 'PRIMARY KEY (' . $this->getFieldListDDL($entity->getPrimaryKey()) . ')';
        }
    }

    /**
     * Returns the DDL SQL to drop the primary key of a entity.
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
ALTER TABLE %s DROP CONSTRAINT %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($entity)),
            $this->quoteIdentifier($this->getPrimaryKeyName($entity))
        );
    }

    /**
     * Returns the DDL SQL to add the primary key of a entity.
     *
     * @param  Entity  $entity From Entity
     * @return string
     */
    public function getAddPrimaryKeyDDL(Entity $entity): string
    {
        if (!$entity->hasPrimaryKey()) {
            return '';
        }

        $pattern = "
ALTER TABLE %s ADD %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($entity)),
            $this->getPrimaryKeyDDL($entity)
        );
    }

    /**
     * Returns the DDL SQL to add the indices of a entity.
     *
     * @param  Entity  $entity To Entity
     * @return string
     */
    public function getAddIndicesDDL(Entity $entity): string
    {
        $ret = '';
        foreach ($entity->getIndices() as $index) {
            $ret .= $this->getAddIndexDDL($index);
        }

        return $ret;
    }

    /**
     * Returns the DDL SQL to add an Index.
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
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($this->getName($index)),
            $this->quoteIdentifier($this->getName($index->getEntity())),
            $this->getFieldListDDL($index->getFields()->toArray())
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
DROP INDEX %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($index))
        );
    }

    /**
     * Builds the DDL SQL for an Index object.
     *
     * @param  Index  $index
     * @return string
     */
    public function getIndexDDL(Index $index): string
    {
        return sprintf(
            '%sINDEX %s (%s)',
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($this->getName($index)),
            $this->getFieldListDDL($index->getFields()->toArray())
        );
    }

    /**
     * Builds the DDL SQL for a Unique constraint object.
     *
     * @param  Unique $unique
     * @return string
     */
    public function getUniqueDDL(Unique $unique): string
    {
        return sprintf('UNIQUE (%s)', $this->getFieldListDDL($unique->getFields()->toArray()));
    }

    /**
     * Builds the DDL SQL to add the foreign keys of a entity.
     *
     * @param  Entity  $entity
     * @return string
     */
    public function getAddRelationsDDL(Entity $entity): string
    {
        $ret = '';
        foreach ($entity->getRelations() as $relation) {
            $ret .= $this->getAddRelationDDL($relation);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to add a foreign key.
     *
     * @param  Relation $relation
     * @return string
     */
    public function getAddRelationDDL(Relation $relation): string
    {
        if ($relation->isSkipSql()) {
            return '';
        }
        $pattern = "
ALTER TABLE %s ADD %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($relation->getEntity())),
            $this->getRelationDDL($relation)
        );
    }

    /**
     * Builds the DDL SQL to drop a foreign key.
     *
     * @param  Relation $relation
     * @return string
     */
    public function getDropRelationDDL(Relation $relation): string
    {
        if ($relation->isSkipSql()) {
            return '';
        }
        $pattern = "
ALTER TABLE %s DROP CONSTRAINT %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($relation->getEntity())),
            $this->quoteIdentifier($this->getName($relation))
        );
    }

    /**
     * Builds the DDL SQL for a Relation object.
     *
     * @param Relation $relation
     * @return string
     */
    public function getRelationDDL(Relation $relation): string
    {
        if ($relation->isSkipSql()) {
            return '';
        }

        $pattern = "CONSTRAINT %s
    FOREIGN KEY (%s)
    REFERENCES %s (%s)";
        $script = sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($relation)),
            $this->getFieldListDDL($relation->getLocalFieldObjects()),
            $this->quoteIdentifier($this->getName($relation->getForeignEntity())),
            $this->getFieldListDDL($relation->getForeignFieldObjects())
        );
        if ($relation->hasOnUpdate()) {
            $script .= "
    ON UPDATE " . $relation->getOnUpdate();
        }
        if ($relation->hasOnDelete()) {
            $script .= "
    ON DELETE " . $relation->getOnDelete();
        }

        return $script;
    }

    /**
     * @param string $comment
     * @return string
     */
    public function getCommentLineDDL(string $comment): string
    {
        $pattern = "-- %s
";

        return sprintf($pattern, $comment);
    }

    /**
     * @param string $comment
     * @return string
     */
    public function getCommentBlockDDL(string $comment): string
    {
        $pattern = "
-----------------------------------------------------------------------
-- %s
-----------------------------------------------------------------------
";

        return sprintf($pattern, $comment);
    }

    /**
     * Builds the DDL SQL to modify a database
     * based on a DatabaseDiff instance
     *
     * @param DatabaseDiff $databaseDiff
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

        foreach ($databaseDiff->getAddedEntities() as $entity) {
            $ret .= $this->getAddEntityDDL($entity);
            $ret .= $this->getAddIndicesDDL($entity);
        }

        foreach ($databaseDiff->getModifiedEntities() as $entityDiff) {
            $ret .= $this->getModifyEntityDDL($entityDiff);
        }

        foreach ($databaseDiff->getAddedEntities() as $entity) {
            $ret .= $this->getAddRelationsDDL($entity);
        }

        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to rename a entity
     *
     * @param string $fromEntityName
     * @param string $toEntityName
     *
     * @return string
     */
    public function getRenameEntityDDL(string $fromEntityName, string $toEntityName): string
    {
        $pattern = "
ALTER TABLE %s RENAME TO %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier(NamingTool::toSnakeCase($fromEntityName)),
            $this->quoteIdentifier(NamingTool::toSnakeCase($toEntityName))
        );
    }

    /**
     * Builds the DDL SQL to alter a entity
     * based on a EntityDiff instance
     *
     * @param EntityDiff $entityDiff
     * @return string
     */
    public function getModifyEntityDDL(EntityDiff $entityDiff): string
    {
        $ret = '';

        $toEntity = $entityDiff->getToEntity();

        // drop indices, foreign keys
        foreach ($entityDiff->getRemovedFks() as $relation) {
            $ret .= $this->getDropRelationDDL($relation);
        }
        foreach ($entityDiff->getModifiedFks() as $fkModification) {
            list($fromFk) = $fkModification;
            $ret .= $this->getDropRelationDDL($fromFk);
        }
        foreach ($entityDiff->getRemovedIndices() as $index) {
            $ret .= $this->getDropIndexDDL($index);
        }
        foreach ($entityDiff->getModifiedIndices() as $indexModification) {
            list($fromIndex) = $indexModification;
            $ret .= $this->getDropIndexDDL($fromIndex);
        }

        $fieldChanges = '';

        // alter entity structure
        if ($entityDiff->hasModifiedPk()) {
            $fieldChanges .= $this->getDropPrimaryKeyDDL($entityDiff->getFromEntity());
        }
        foreach ($entityDiff->getRenamedFields() as $fieldRenaming) {
            $fieldChanges .= $this->getRenameFieldDDL($fieldRenaming[0], $fieldRenaming[1]);
        }
        if ($modifiedFields = $entityDiff->getModifiedFields()) {
            $fieldChanges .= $this->getModifyFieldsDDL($modifiedFields);
        }
        if ($addedFields = $entityDiff->getAddedFields()) {
            $fieldChanges .= $this->getAddFieldsDDL($addedFields->toArray());
        }
        foreach ($entityDiff->getRemovedFields() as $field) {
            $fieldChanges .= $this->getRemoveFieldDDL($field);
        }

        // add new indices and foreign keys
        if ($entityDiff->hasModifiedPk()) {
            $fieldChanges .= $this->getAddPrimaryKeyDDL($entityDiff->getToEntity());
        }

        if ($fieldChanges) {
            //merge field changes into one command. This is more compatible especially with PK constraints.

            $changes = explode(';', $fieldChanges);
            $fieldChanges = [];

            foreach ($changes as $change) {
                if (!trim($change)) {
                    continue;
                }
                $isCompatibleCall = preg_match(
                    sprintf('/ALTER TABLE %s (?!RENAME)/', $this->quoteIdentifier($this->getName($toEntity))),
                    $change
                );
                if ($isCompatibleCall) {
                    $fieldChanges[] = preg_replace(
                        sprintf('/ALTER TABLE %s /', $this->quoteIdentifier($this->getName($toEntity))),
                        "\n\n  ",
                        trim($change)
                    );
                } else {
                    $ret .= $change.";\n";
                }
            }

            if (0 < count($fieldChanges)) {
                $ret .= sprintf(
                    "
ALTER TABLE %s%s;
",
                    $this->quoteIdentifier($this->getName($toEntity)),
                    implode(',', $fieldChanges)
                );
            }
        }

        // create indices, foreign keys
        foreach ($entityDiff->getModifiedIndices() as $indexModification) {
            list($oldIndex, $toIndex) = $indexModification;
            $ret .= $this->getAddIndexDDL($toIndex);
        }
        foreach ($entityDiff->getAddedIndices() as $index) {
            $ret .= $this->getAddIndexDDL($index);
        }
        foreach ($entityDiff->getModifiedFks() as $fkModification) {
            list(, $toFk) = $fkModification;
            $ret .= $this->getAddRelationDDL($toFk);
        }
        foreach ($entityDiff->getAddedFks() as $relation) {
            $ret .= $this->getAddRelationDDL($relation);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a entity
     * based on a EntityDiff instance
     *
     * @param EntityDiff $entityDiff
     * @return string
     */
    public function getModifyEntityFieldsDDL(EntityDiff $entityDiff): string
    {
        $ret = '';

        foreach ($entityDiff->getRemovedFields() as $field) {
            $ret .= $this->getRemoveFieldDDL($field);
        }

        foreach ($entityDiff->getRenamedFields() as $fieldRenaming) {
            $ret .= $this->getRenameFieldDDL($fieldRenaming[0], $fieldRenaming[1]);
        }

        if ($modifiedFields = $entityDiff->getModifiedFields()) {
            $ret .= $this->getModifyFieldsDDL($modifiedFields);
        }

        if ($addedFields = $entityDiff->getAddedFields()) {
            $ret .= $this->getAddFieldsDDL($addedFields->toArray());
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a entity's primary key
     * based on a EntityDiff instance
     *
     * @param EntityDiff $entityDiff
     * @return string
     */
    public function getModifyEntityPrimaryKeyDDL(EntityDiff $entityDiff): string
    {
        $ret = '';

        if ($entityDiff->hasModifiedPk()) {
            $ret .= $this->getDropPrimaryKeyDDL($entityDiff->getFromEntity());
            $ret .= $this->getAddPrimaryKeyDDL($entityDiff->getToEntity());
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a entity's indices
     * based on a EntityDiff instance
     *
     * @param EntityDiff $entityDiff
     * @return string
     */
    public function getModifyEntityIndicesDDL(EntityDiff $entityDiff): string
    {
        $ret = '';

        foreach ($entityDiff->getRemovedIndices() as $index) {
            $ret .= $this->getDropIndexDDL($index);
        }

        foreach ($entityDiff->getAddedIndices() as $index) {
            $ret .= $this->getAddIndexDDL($index);
        }

        foreach ($entityDiff->getModifiedIndices() as $indexModification) {
            list($fromIndex, $toIndex) = $indexModification;
            $ret .= $this->getDropIndexDDL($fromIndex);
            $ret .= $this->getAddIndexDDL($toIndex);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a entity's foreign keys
     * based on a EntityDiff instance
     *
     * @param EntityDiff $entityDiff
     * @return string
     */
    public function getModifyEntityRelationsDDL(EntityDiff $entityDiff): string
    {
        $ret = '';

        foreach ($entityDiff->getRemovedFks() as $relation) {
            $ret .= $this->getDropRelationDDL($relation);
        }

        foreach ($entityDiff->getAddedFks() as $relation) {
            $ret .= $this->getAddRelationDDL($relation);
        }

        foreach ($entityDiff->getModifiedFks() as $fkModification) {
            list($fromFk, $toFk) = $fkModification;
            $ret .= $this->getDropRelationDDL($fromFk);
            $ret .= $this->getAddRelationDDL($toFk);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to remove a field
     *
     * @param Field $field
     * @return string
     */
    public function getRemoveFieldDDL(Field $field): string
    {
        $pattern = "
ALTER TABLE %s DROP COLUMN %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($field->getEntity())),
            $this->quoteIdentifier($this->getName($field))
        );
    }

    /**
     * Builds the DDL SQL to rename a field
     *
     * @param Field $fromField
     * @param Field $toField
     *
     * @return string
     */
    public function getRenameFieldDDL(Field $fromField, Field $toField): string
    {
        $pattern = "
ALTER TABLE %s RENAME COLUMN %s TO %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($fromField->getEntity())),
            $this->quoteIdentifier($this->getName($fromField)),
            $this->quoteIdentifier($this->getName($toField))
        );
    }

    /**
     * Builds the DDL SQL to modify a field
     *
     * @param FieldDiff $fieldDiff
     *
     * @return string
     */
    public function getModifyFieldDDL(FieldDiff $fieldDiff): string
    {
        $toField = $fieldDiff->getToField();
        $pattern = "
ALTER TABLE %s MODIFY %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($toField->getEntity())),
            $this->getFieldDDL($toField)
        );
    }

    /**
     * Builds the DDL SQL to modify a list of fields
     *
     * @param  FieldDiff[] $fieldDiffs
     * @return string
     */
    public function getModifyFieldsDDL(Map $fieldDiffs): string
    {
        $lines = [];
        $entity = null;
        foreach ($fieldDiffs as $fieldDiff) {
            $toField = $fieldDiff->getToField();
            if (null === $entity) {
                $entity = $toField->getEntity();
            }
            $lines[] = $this->getFieldDDL($toField);
        }

        $sep = ",
    ";

        $pattern = "
ALTER TABLE %s MODIFY
(
    %s
);
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($entity)),
            implode($sep, $lines)
        );
    }

    /**
     * Builds the DDL SQL to remove a field
     *
     * @param Field $field
     *
     * @return string
     */
    public function getAddFieldDDL(Field $field): string
    {
        $pattern = "
ALTER TABLE %s ADD %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($field->getEntity())),
            $this->getFieldDDL($field)
        );
    }

    /**
     * Builds the DDL SQL to remove a list of fields
     *
     * @param  Field[] $fields
     * @return string
     */
    public function getAddFieldsDDL(array $fields): string
    {
        $lines = [];
        $entity = null;
        foreach ($fields as $field) {
            if (null === $entity) {
                $entity = $field->getEntity();
            }
            $lines[] = $this->getFieldDDL($field);
        }

        $sep = ",
    ";

        $pattern = "
ALTER TABLE %s ADD
(
    %s
);
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($this->getName($entity)),
            implode($sep, $lines)
        );
    }

    /**
     * Returns if the RDBMS-specific SQL type has a size attribute.
     *
     * @param  string  $sqlType the SQL type
     * @return boolean True if the type has a size attribute
     */
    public function hasSize(string $sqlType): bool
    {
        return true;
    }

    /**
     * Returns if the RDBMS-specific SQL type has a scale attribute.
     *
     * @param  string  $sqlType the SQL type
     * @return boolean True if the type has a scale attribute
     */
    public function hasScale(string $sqlType): bool
    {
        return true;
    }

    /**
     * Quote and escape needed characters in the string for underlying RDBMS.
     *
     * @param  string $text
     * @return string
     */
    public function quote(string $text): string
    {
        if ($con = $this->getConnection()) {
            return $con->quote($text);
        } else {
            return "'" . $this->disconnectedEscapeText($text) . "'";
        }
    }

    /**
     * Method to escape text when no connection has been set.
     *
     * The subclasses can implement this using string replacement functions
     * or native DB methods.
     *
     * @param  string $text Text that needs to be escaped.
     * @return string
     */
    protected function disconnectedEscapeText(string $text): string
    {
        return str_replace("'", "''", $text);
    }

    /**
     * Quotes identifiers used in database SQL if isIdentifierQuotingEnabled is true.
     * Calls doQuoting() when identifierQuoting is enabled.
     *
     * @param  string $text
     * @return string Quoted identifier.
     */
    protected function quoteIdentifier(string $text): string
    {
        return $this->isIdentifierQuotingEnabled() ? $this->doQuoting($text) : $text;
    }

    /**
     * {@inheritdoc}
     */
    public function doQuoting(string $text): string
    {
        return '"' . strtr($text, ['.' => '"."']) . '"';
    }

    /**
     * Whether RDBMS supports native ON DELETE triggers (e.g. ON DELETE CASCADE).
     * @return boolean
     */
    public function supportsNativeDeleteTrigger(): bool
    {
        return false;
    }

    /**
     * Whether RDBMS supports INSERT null values in autoincremented primary keys
     * @return boolean
     */
    public function supportsInsertNullPk(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportsIndexSize(): bool
    {
        return false;
    }

    /**
     * Whether the underlying PDO driver for this platform returns BLOB fields as streams (instead of strings).
     *
     * @return boolean
     */
    public function hasStreamBlobImpl(): bool
    {
        return false;
    }

    /**
     * @see Platform::supportsSchemas()
     */
    public function supportsSchemas(): bool
    {
        return false;
    }

    /**
     * @see Platform::supportsMigrations()
     */
    public function supportsMigrations(): bool
    {
        return true;
    }


    public function supportsVarcharWithoutSize(): bool
    {
        return false;
    }
    /**
     * Returns the Boolean value for the RDBMS.
     *
     * This value should match the Boolean value that is set
     * when using Propel's PreparedStatement::setBoolean().
     *
     * This function is used to set default field values when building
     * SQL.
     *
     * @param  mixed $tf A Boolean or string representation of Boolean ('y', 'true').
     * @return mixed
     */
    public function getBooleanString($b)
    {
        if (is_bool($b) && true === $b) {
            return '1';
        }

        if (is_int($b) && 1 === $b) {
            return '1';
        }

        if (is_string($b)
            && in_array(strtolower($b), ['1', 'true', 'y', 'yes'])) {
            return '1';
        }

        return '0';
    }

    public function getPhpArrayString($stringValue)
    {
        $stringValue = trim($stringValue);
        if (empty($stringValue)) {
            return null;
        }

        $values = [];
        foreach (explode(',', $stringValue) as $v) {
            $values[] = trim($v);
        }

        $value = implode($values, ' | ');
        if (empty($value) || ' | ' === $value) {
            return null;
        }

        return $this->quote(sprintf('||%s||', $value));
    }

    /**
     * Gets the preferred timestamp formatter for setting date/time values.
     * @return string
     */
    public function getTimestampFormatter(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Gets the preferred time formatter for setting date/time values.
     * @return string
     */
    public function getTimeFormatter(): string
    {
        return 'H:i:s';
    }

    /**
     * Gets the preferred date formatter for setting date/time values.
     * @return string
     */
    public function getDateFormatter(): string
    {
        return 'Y-m-d';
    }

    /**
     * Get the PHP snippet for binding a value to a field.
     * Warning: duplicates logic from AdapterInterface::bindValue().
     * Any code modification here must be ported there.
     */
    public function getFieldBindingPHP(Field $field, $identifier, $fieldValueAccessor, $tab = "            ")
    {
        $script = '';
        if ($field->isTemporalType()) {
            $fieldValueAccessor = $fieldValueAccessor . " ? " . $fieldValueAccessor . "->format(\""  . $this->getTimeStampFormatter() . "\") : null";
        } elseif ($field->isLobType()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            $script .= "
if (is_resource($fieldValueAccessor)) {
    rewind($fieldValueAccessor);
}";
        }

        $script .= sprintf(
            "
\$stmt->bindValue(%s, %s, %s);",
            $identifier,
            $fieldValueAccessor,
            PropelTypes::getPdoTypeString($field->getType())
        );

        return preg_replace('/^(.+)/m', $tab . '$1', $script);
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from AdapterInterface::getId().
     * Any code modification here must be ported there.
     *
     * Typical output:
     * <code>
     * $this->id = $con->lastInsertId();
     * </code>
     */
    public function getIdentifierPhp($fieldValueMutator, $connectionVariableName = '$con', $sequenceName = '', $tab = "            ")
    {
        return sprintf(
            "
%s%s = %s->lastInsertId(%s);",
            $tab,
            $fieldValueMutator,
            $connectionVariableName,
            $sequenceName ? ("'" . $sequenceName . "'") : ''
        );
    }

    /**
     * Returns a integer indexed array of default type sizes.
     *
     * @return integer[] type indexed array of integers
     */
    public function getDefaultTypeSizes(): array
    {
        return [];
    }

    /**
     * Returns the default size of a specific type.
     *
     * @param string $type
     * @return integer
     */
    public function getDefaultTypeSize(string $type): ?int
    {
        $sizes = $this->getDefaultTypeSizes();

        return $sizes[strtolower($type)] ?? null;
    }

    /**
     * Normalizes a entity for the current platform. Very important for the EntityComparator to not
     * generate useless diffs.
     * Useful for checking needed definitions/structures. E.g. Unique Indexes for Relation fields,
     * which the most Platforms requires but which is not always explicitly defined in the entity model.
     *
     * @param Entity $entity The entity object which gets modified.
     */
    public function normalizeEntity(Entity $entity)
    {
        if ($entity->hasRelations()) {
            foreach ($entity->getRelations() as $relation) {
                if ($relation->getForeignEntity() && !$relation->getForeignEntity()->isUnique($relation->getForeignFieldObjects())) {
                    $unique = new Unique();
                    $unique->addFields($relation->getForeignFieldObjects());
                    $relation->getForeignEntity()->addUnique($unique);
                }
            }
        }

//        if (!$this->supportsIndexSize() && $entity->getIndices()) {
//            // when the plafform does not support index sizes we reset it
//            foreach ($entity->getIndices() as $index) {
//                $index->resetFieldsSize();
//            }
//        }

        foreach ($entity->getFields() as $field) {
            if ($field->getSize() && $defaultSize = $this->getDefaultTypeSize($field->getType())) {
                if (intval($field->getSize()) === $defaultSize) {
                    $field->setSize(null);
                }
            }
        }
    }

    /**
     * MOVED HERE FROM Propel\Generator\Model\Entity
     *
     * Returns a delimiter-delimited string list of field names.
     *
     * @see SqlDefaultPlatform::getFieldList() if quoting is required
     *
     * @param array
     * @param string $delimiter
     * @return string
     */
    public function getFieldList(array $columns, string $delimiter = ','): string
    {
        $list = [];
        foreach ($columns as $col) {
            if ($col instanceof Field) {
                $col = $col->getName();
            }
            $list[] = $col;
        }
        return implode($delimiter, $list);
    }
}
