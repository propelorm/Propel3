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
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\NamingTool;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Vendor;

/**
 * Postgresql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Niklas Närhinen <niklas@narhinen.net>
 */
class PgsqlPlatform extends SqlDefaultPlatform
{

    /**
     * @var string
     */
    protected $createOrDropSequences = '';

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, 'BOOLEAN'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, 'INT2'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, 'INT2'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, 'INT8'));
        //$this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, 'FLOAT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, 'DOUBLE PRECISION'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::FLOAT, 'DOUBLE PRECISION'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'ENUM'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DECIMAL, 'NUMERIC'));
    }

    public function getNativeIdMethod(): string
    {
        return PlatformInterface::SERIAL;
    }

    public function getAutoIncrement(): string
    {
        return '';
    }

    public function getDefaultTypeSizes(): array
    {
        return [
            'char'      => 1,
            'character' => 1,
            'integer'   => 32,
            'bigint'    => 64,
            'smallint'  => 16,
            'double precision' => 54
        ];
    }

    public function getMaxFieldNameLength(): int
    {
        return 32;
    }

    public function getBooleanString($b)
    {
        // parent method does the checking for allows string
        // representations & returns integer
        $b = parent::getBooleanString($b);

        return ($b ? "'t'" : "'f'");
    }

    public function supportsNativeDeleteTrigger(): bool
    {
        return true;
    }

    /**
     * Override to provide sequence names that conform to postgres' standard when
     * no id-method-parameter specified.
     *
     * @param Entity $entity
     *
     * @return string
     */
    public function getSequenceName(Entity $entity): string
    {
        $result = '';
        if ($entity->getIdMethod() == Model::ID_METHOD_NATIVE) {
            $idMethodParams = $entity->getIdMethodParameters();
            if (empty($idMethodParams)) {
                $result = null;
                // We're going to ignore a check for max length (mainly
                // because I'm not sure how Postgres would handle this w/ SERIAL anyway)
                foreach ($entity->getFields() as $col) {
                    if ($col->isAutoIncrement()) {
                        $result = $entity->getTableName() . '_' . $col->getName() . '_seq';
                        break; // there's only one auto-increment field allowed
                    }
                }
            } else {
                $result = $idMethodParams[0]->getValue();
            }
        }

        return $result;
    }

    protected function getAddSequenceDDL(Entity $entity): string
    {
        if ($entity->getIdMethod() == Model::ID_METHOD_NATIVE
         && $entity->getIdMethodParameters() != null) {
            $pattern = "
CREATE SEQUENCE %s;
";

            return sprintf(
                $pattern,
                $this->quoteIdentifier(strtolower($this->getSequenceName($entity)))
            );
        }

        return '';
    }

    protected function getDropSequenceDDL(Entity $entity): string
    {
        if ($entity->getIdMethod() == Model::ID_METHOD_NATIVE
         && $entity->getIdMethodParameters() != null) {
            $pattern = "
DROP SEQUENCE %s;
";

            return sprintf(
                $pattern,
                $this->quoteIdentifier(strtolower($this->getSequenceName($entity)))
            );
        }

        return '';
    }

    public function getAddSchemasDDL(Database $database): string
    {
        $ret = '';
        $schemas = [];
        foreach ($database->getEntities() as $entity) {
            $vi = $entity->getVendorByType('pgsql');
            if ($vi->hasParameter('schema') && !isset($schemas[$vi->getParameter('schema')])) {
                $schemas[$vi->getParameter('schema')] = true;
                $ret .= $this->getAddSchemaDDL($entity);
            }
        }

        return $ret;
    }

    public function getAddSchemaDDL(Entity $entity): string
    {
        $vi = $entity->getVendorByType('pgsql');
        if ($vi->hasParameter('schema')) {
            $pattern = "
CREATE SCHEMA %s;
";

            return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
        };
    }

    public function getUseSchemaDDL(Entity $entity): string
    {
        $vi = $entity->getVendorByType('pgsql');
        if ($vi->hasParameter('schema')) {
            $pattern = "
SET search_path TO %s;
";

            return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
        }

        return '';
    }

    public function getResetSchemaDDL(Entity $entity): string
    {
        $vi = $entity->getVendorByType('pgsql');
        if ($vi->hasParameter('schema')) {
            return "
SET search_path TO public;
";
        }

        return '';
    }

    public function getAddEntitiesDDL(Database $database): string
    {
        $ret = $this->getBeginDDL();
        $ret .= $this->getAddSchemasDDL($database);

        foreach ($database->getEntitiesForSql() as $entity) {
            $this->normalizeEntity($entity);
        }

        foreach ($database->getEntitiesForSql() as $entity) {
            $ret .= $this->getCommentBlockDDL($entity->getFullTableName());
            $ret .= $this->getDropEntityDDL($entity);
            $ret .= $this->getAddEntityDDL($entity);
            $ret .= $this->getAddIndicesDDL($entity);
        }
        foreach ($database->getEntitiesForSql() as $entity) {
            $ret .= $this->getAddRelationsDDL($entity);
        }
        $ret .= $this->getEndDDL();

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function getAddRelationsDDL(Entity $entity): string
    {
        $ret = '';
        foreach ($entity->getRelations() as $relation) {
            $ret .= $this->getAddRelationDDL($relation);
        }

        return $ret;
    }

    public function getAddEntityDDL(Entity $entity): string
    {
        $ret = '';
        $ret .= $this->getUseSchemaDDL($entity);
        $ret .= $this->getAddSequenceDDL($entity);

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
CREATE TABLE %s
(
    %s
);
";
        $ret .= sprintf(
            $pattern,
            $this->quoteIdentifier($entity->getFullTableName()),
            implode($sep, $lines)
        );

        if ($entity->hasDescription()) {
            $pattern = "
COMMENT ON TABLE %s IS %s;
";
            $ret .= sprintf(
                $pattern,
                $this->quoteIdentifier($entity->getFullTableName()),
                $this->quote($entity->getDescription())
            );
        }

        $ret .= $this->getAddFieldsComments($entity);
        $ret .= $this->getResetSchemaDDL($entity);

        return $ret;
    }

    protected function getAddFieldsComments(Entity $entity): string
    {
        $ret = '';
        foreach ($entity->getFields() as $field) {
            $ret .= $this->getAddFieldComment($field);
        }

        return $ret;
    }

    protected function getAddFieldComment(Field $field): string
    {
        $pattern = "
COMMENT ON COLUMN %s.%s IS %s;
";
        if ($description = $field->getDescription()) {
            return sprintf(
                $pattern,
                $this->quoteIdentifier($field->getEntity()->getFullTableName()),
                $this->quoteIdentifier($field->getColumnName()),
                $this->quote($description)
            );
        }

        return '';
    }

    public function getDropEntityDDL(Entity $entity): string
    {
        $ret = '';
        $ret .= $this->getUseSchemaDDL($entity);
        $pattern = "
DROP TABLE IF EXISTS %s CASCADE;
";
        $ret .= sprintf($pattern, $this->quoteIdentifier($entity->getFullTableName()));
        $ret .= $this->getDropSequenceDDL($entity);
        $ret .= $this->getResetSchemaDDL($entity);

        return $ret;
    }

    public function getPrimaryKeyName(Entity $entity): string
    {
        $entityName = $entity->getTableName();

        return $entityName . '_pkey';
    }

    public function getFieldDDL(Field $col): string
    {
        $domain = $col->getDomain();
        if (!$col->isDefaultSqlType($this) && !$col->getDomain()->isReplaced()) {
            $domain = $this->getDomainForType($col->getType());
        }

        $ddl = [$this->quoteIdentifier($col->getColumnName())];
        $sqlType = $domain->getSqlType();
        $entity = $col->getEntity();
        if ($col->isAutoIncrement() && $entity && $entity->getIdMethodParameters() == null) {
            $sqlType = $col->getType() === PropelTypes::BIGINT ? 'bigserial' : 'serial';
        }
        if ($this->hasSize($sqlType) && !$col->getDomain()->isReplaced()) {
            if ($this->isNumber($sqlType)) {
                if ('NUMERIC' === strtoupper($sqlType)) {
                    $ddl[] = $sqlType . $col->getSizeDefinition();
                } else {
                    $ddl[] = $sqlType;
                }
            } else {
                $ddl[] = $sqlType . $col->getSizeDefinition();
            }
        } else {
            $ddl[] = $sqlType;
        }

        if ($sqlType == "ENUM") {
            $ddl[] = '("' . implode('","', $col->getValueSet()) . '")';
        }
        
        if ($default = $this->getFieldDefaultValueDDL($col)) {
            $ddl[] = $default;
        }
        if ($col->isNotNull()) {
            $ddl[] = $this->getNotNullString();
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }

        return implode(' ', $ddl);
    }

    public function getUniqueDDL(Unique $unique): string
    {
        return sprintf(
            'CONSTRAINT %s UNIQUE (%s)',
            $this->quoteIdentifier($unique->getName()),
            $this->getFieldListDDL($unique->getFields())
        );
    }

    public function getRenameEntityDDL($fromEntityName, $toEntityName): string
    {
        if (false !== ($pos = strpos($toEntityName, '.'))) {
            $toEntityName = substr($toEntityName, $pos + 1);
        }

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
     * @see Platform::supportsSchemas()
     */

    public function supportsSchemas(): bool
    {
        return true;
    }

    public function hasSize(string $sqlType): bool
    {
        return !in_array($sqlType, ['BYTEA', 'TEXT', 'DOUBLE PRECISION']);
    }

    public function hasStreamBlobImpl(): bool
    {
        return true;
    }

    public function supportsVarcharWithoutSize(): bool
    {
        return true;
    }

    public function getModifyEntityDDL(EntityDiff $entityDiff): string
    {
        $ret = parent::getModifyEntityDDL($entityDiff);

        if ($this->createOrDropSequences) {
            $ret = $this->createOrDropSequences . $ret;
        }

        $this->createOrDropSequences = '';

        return $ret;
    }

    /**
     * Overrides the implementation from SqlDefaultPlatform
     *
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @return string
     * @see DefaultPlatform::getModifyFieldDDL
     */
    public function getModifyFieldDDL(FieldDiff $fieldDiff): string
    {
        $ret = '';
        $changedProperties = $fieldDiff->getChangedProperties();

        $fromField = $fieldDiff->getFromField();
        $toField = clone $fieldDiff->getToField();

        $fromEntity = $fromField->getEntity();
        $entity = $toField->getEntity();

        $colName = $this->quoteIdentifier($toField->getColumnName());

        $pattern = "
ALTER TABLE %s ALTER COLUMN %s;
";

        if (isset($changedProperties['autoIncrement'])) {
            $tableName = $entity->getTableName();
            $colPlainName = $toField->getColumnName();
            $seqName = "{$tableName}_{$colPlainName}_seq";

            if ($toField->isAutoIncrement() && $entity && $entity->getIdMethodParameters() == null) {
                $defaultValue = "nextval('$seqName'::regclass)";
                $toField->setDefaultValue($defaultValue);
                $changedProperties['defaultValueValue'] = [null, $defaultValue];

                //add sequence
                if (!$fromEntity->getDatabase()->hasSequence($seqName)) {
                    $this->createOrDropSequences .= sprintf(
                        "
CREATE SEQUENCE %s;
",
                        $seqName
                    );
                    $fromEntity->getDatabase()->addSequence($seqName);
                }
            }

            if (!$toField->isAutoIncrement() && $fromField->isAutoIncrement()) {
                $changedProperties['defaultValueValue'] = [$fromField->getDefaultValueString(), null];
                $toField->setDefaultValue(null);

                //remove sequence
                if ($fromEntity->getDatabase()->hasSequence($seqName)) {
                    $this->createOrDropSequences .= sprintf(
                        "
DROP SEQUENCE %s CASCADE;
",
                        $seqName
                    );
                    $fromEntity->getDatabase()->removeSequence($seqName);
                }
            }
        }

        if (isset($changedProperties['size']) || isset($changedProperties['type']) || isset($changedProperties['scale'])) {
            $sqlType = $toField->getDomain()->getSqlType();

            if ($this->hasSize($sqlType) && $toField->isDefaultSqlType($this)) {
                if ($this->isNumber($sqlType)) {
                    if ('NUMERIC' === strtoupper($sqlType)) {
                        $sqlType .= $toField->getSizeDefinition();
                    }
                } else {
                    $sqlType .= $toField->getSizeDefinition();
                }
            }

            if ($using = $this->getUsingCast($fromField, $toField)) {
                $sqlType .= $using;
            }
            $ret .= sprintf(
                $pattern,
                $this->quoteIdentifier($entity->getFullTableName()),
                $colName . ' TYPE ' . $sqlType
            );
        }

        if (isset($changedProperties['defaultValueValue'])) {
            $property = $changedProperties['defaultValueValue'];
            if ($property[0] !== null && $property[1] === null) {
                $ret .= sprintf($pattern, $this->quoteIdentifier($entity->getFullTableName()), $colName . ' DROP DEFAULT');
            } else {
                $ret .= sprintf($pattern, $this->quoteIdentifier($entity->getFullTableName()), $colName . ' SET ' . $this->getFieldDefaultValueDDL($toField));
            }
        }

        if (isset($changedProperties['notNull'])) {
            $property = $changedProperties['notNull'];
            $notNull = ' DROP NOT NULL';
            if ($property[1]) {
                $notNull = ' SET NOT NULL';
            }
            $ret .= sprintf($pattern, $this->quoteIdentifier($entity->getFullTableName()), $colName . $notNull);
        }

        return $ret;
    }

    public function isString(string $type): bool
    {
        $strings = ['VARCHAR'];

        return in_array(strtoupper($type), $strings);
    }

    public function isNumber(string $type): bool
    {
        $numbers = ['INTEGER', 'INT4', 'INT2', 'NUMBER', 'NUMERIC', 'SMALLINT', 'BIGINT', 'DECIMAL', 'REAL', 'DOUBLE PRECISION', 'SERIAL', 'BIGSERIAL'];

        return in_array(strtoupper($type), $numbers);
    }

    public function getUsingCast(Field $fromField, Field $toField): string
    {
        $fromSqlType = strtoupper($fromField->getDomain()->getSqlType());
        $toSqlType = strtoupper($toField->getDomain()->getSqlType());
        $name = $fromField->getColumnName();

        if ($this->isNumber($fromSqlType) && $this->isString($toSqlType)) {
            //cast from int to string
            return '  ';
        }
        if ($this->isString($fromSqlType) && $this->isNumber($toSqlType)) {
            //cast from string to int
            return "
   USING CASE WHEN trim($name) SIMILAR TO '[0-9]+'
        THEN CAST(trim($name) AS integer)
        ELSE NULL END";
        }

        if ($this->isNumber($fromSqlType) && 'BYTEA' === $toSqlType) {
            return " USING decode(CAST($name as text), 'escape')";
        }

        if ('DATE' === $fromSqlType && 'TIME' === $toSqlType) {
            return " USING NULL";
        }

        if ($this->isNumber($fromSqlType) && $this->isNumber($toSqlType)) {
            return '';
        }

        if ($this->isString($fromSqlType) && $this->isString($toSqlType)) {
            return '';
        }

        return " USING NULL";
    }

    /**
     * Overrides the implementation from SqlDefaultPlatform
     *
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @param Map $fieldDiffs Map of FieldDiff objects
     * @return string
     * @see DefaultPlatform::getModifyFieldsDDL
     */
    public function getModifyFieldsDDL(Map $fieldDiffs): string
    {
        $ret = '';
        foreach ($fieldDiffs as $fieldDiff) {
            $ret .= $this->getModifyFieldDDL($fieldDiff);
        }

        return $ret;
    }

    /**
     * Overrides the implementation from SqlDefaultPlatform
     *
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @return string
     * @see DefaultPlatform::getAddFieldsDLL
     */
    public function getAddFieldsDDL(array $fields): string
    {
        $ret = '';
        foreach ($fields as $field) {
            $ret .= $this->getAddFieldDDL($field);
        }

        return $ret;
    }

    /**
     * Overrides the implementation from SqlDefaultPlatform
     *
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @return string
     * @see DefaultPlatform::getDropIndexDDL
     */
    public function getDropIndexDDL(Index $index): string
    {
        if ($index instanceof Unique) {
            $pattern = "
    ALTER TABLE %s DROP CONSTRAINT %s;
    ";

            return sprintf(
                $pattern,
                $this->quoteIdentifier($index->getEntity()->getFullTableName()),
                $this->quoteIdentifier($index->getName())
            );
        } else {
            return parent::getDropIndexDDL($index);
        }
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from PgsqlAdapter::getId().
     * Any code modification here must be ported there.
     */
    public function getIdentifierPhp($fieldValueMutator, $connectionVariableName = '$con', $sequenceName = '', $tab = "            ")
    {
        if (!$sequenceName) {
            throw new EngineException('PostgreSQL needs a sequence name to fetch primary keys');
        }
        $snippet = "
\$dataFetcher = %s->query(\"SELECT nextval('%s')\");
%s = \$dataFetcher->fetchField();";
        $script = sprintf(
            $snippet,
            $connectionVariableName,
            $sequenceName,
            $fieldValueMutator
        );

        return preg_replace('/^/m', $tab, $script);
    }
}
