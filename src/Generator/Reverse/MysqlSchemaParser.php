<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Reverse;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\NamingTool;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\FieldDefaultValue;

/**
 * Mysql database schema parser.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class MysqlSchemaParser extends AbstractSchemaParser
{
    /**
     * @var boolean
     */
    private $addVendorInfo = false;

    /**
     * Map MySQL native types to Propel types.
     * @var array
     */
    private static $mysqlTypeMap = [
        'tinyint'    => PropelTypes::TINYINT,
        'smallint'   => PropelTypes::SMALLINT,
        'mediumint'  => PropelTypes::SMALLINT,
        'int'        => PropelTypes::INTEGER,
        'integer'    => PropelTypes::INTEGER,
        'bigint'     => PropelTypes::BIGINT,
        'int24'      => PropelTypes::BIGINT,
        'real'       => PropelTypes::DOUBLE,
        'float'      => PropelTypes::FLOAT,
        'decimal'    => PropelTypes::DECIMAL,
        'numeric'    => PropelTypes::NUMERIC,
        'double'     => PropelTypes::DOUBLE,
        'char'       => PropelTypes::CHAR,
        'varchar'    => PropelTypes::VARCHAR,
        'date'       => PropelTypes::DATE,
        'time'       => PropelTypes::TIME,
        'year'       => PropelTypes::INTEGER,
        'datetime'   => PropelTypes::TIMESTAMP,
        'timestamp'  => PropelTypes::TIMESTAMP,
        'tinyblob'   => PropelTypes::BINARY,
        'blob'       => PropelTypes::BLOB,
        'mediumblob' => PropelTypes::VARBINARY,
        'longblob'   => PropelTypes::LONGVARBINARY,
        'longtext'   => PropelTypes::CLOB,
        'tinytext'   => PropelTypes::VARCHAR,
        'mediumtext' => PropelTypes::LONGVARCHAR,
        'text'       => PropelTypes::LONGVARCHAR,
        'enum'       => PropelTypes::CHAR,
        'set'        => PropelTypes::CHAR,
    ];

    protected static $defaultTypeSizes = [
        'char'     => 1,
        'tinyint'  => 4,
        'smallint' => 6,
        'int'      => 11,
        'bigint'   => 20,
        'decimal'  => 10,
    ];

    /**
     * Gets a type mapping from native types to Propel types
     *
     * @return array
     */
    protected function getTypeMapping()
    {
        return self::$mysqlTypeMap;
    }

    /**
     * @param  Database $database
     * @param  Entity[]  $additionalEntities
     * @return int
     */
    public function parse(Database $database, array $additionalEntities = [])
    {
        if (null !== $this->getGeneratorConfig()) {
            $this->addVendorInfo = $this->getGeneratorConfig()->get()['migrations']['addVendorInfo'];
        }

        $this->parseTables($database);
        foreach ($additionalEntities as $entity) {
            $this->parseTables($database, $entity);
        }

        // Now populate only fields.
        foreach ($database->getEntities() as $entity) {
            $this->addFields($entity);
        }

        // Now add indices and constraints.
        foreach ($database->getEntities() as $entity) {
            $this->addRelations($entity);
            $this->addIndexes($entity);
            $this->addPrimaryKey($entity);

            $this->addEntityVendorInfo($entity);
        }

        return count($database->getEntities());
    }

    protected function parseTables(Database $database, $filterEntity = null)
    {
        $sql = 'SHOW FULL TABLES';

        if ($filterEntity) {
            if ($schema = $filterEntity->getSchema()) {
                $sql .= ' FROM ' . $database->getPlatform()->doQuoting($schema);
            }
            $sql .= sprintf(" LIKE '%s'", $filterEntity->getCommonName());
        } elseif ($schema = $database->getSchema()) {
            $sql .= ' FROM ' . $database->getPlatform()->doQuoting($schema);
        }

        $dataFetcher = $this->dbh->query($sql);

        // First load the entities (important that this happen before filling out details of entities)
        $entities = [];
        foreach ($dataFetcher as $row) {
            $name = $row[0];
            $type = $row[1];

            if ($name == $this->getMigrationTable() || $type !== 'BASE TABLE') {
                continue;
            }

            $entity = new Entity(NamingTool::toCamelCase($name));
            $entity->setTableName($name);
            $entity->setIdMethod($database->getDefaultIdMethod());
            if ($filterEntity && $filterEntity->getSchema()) {
                $entity->setSchema($filterEntity->getSchema());
            }
            $database->addEntity($entity);
            $entities[] = $entity;
        }
    }

    /**
     * Adds Fields to the specified entity.
     *
     * @param Entity $entity The Entity model class to add fields to.
     */
    protected function addFields(Entity $entity)
    {
        $stmt = $this->dbh->query(sprintf('SHOW COLUMNS FROM %s', $this->getPlatform()->doQuoting($entity->getFullTableName())));

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $field = $this->getFieldFromRow($row, $entity);
            $entity->addField($field);
        }
    }

    /**
     * Factory method creating a Field object
     * based on a row from the 'show fields from ' MySQL query result.
     *
     * @param  array  $row An associative array with the following keys:
     *                     Field, Type, Null, Key, Default, Extra.
     * @return Field
     */
    public function getFieldFromRow($row, Entity $entity)
    {
        $name = $row['Field'];
        $isNullable = ('YES' === $row['Null']);
        $autoincrement = (false !== strpos($row['Extra'], 'auto_increment'));
        $size = null;
        $scale = null;
        $sqlType = false;

        $regexp = '/^
            (\w+)        # field type [1]
            [\(]         # (
                ?([\d,]*)  # size or size, precision [2]
            [\)]         # )
            ?\s*         # whitespace
            (\w*)        # extra description (UNSIGNED, CHARACTER SET, ...) [3]
        $/x';
        if (preg_match($regexp, $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($matches[2]) {
                if (false !== ($cpos = strpos($matches[2], ','))) {
                    $size = (int) substr($matches[2], 0, $cpos);
                    $scale = (int) substr($matches[2], $cpos + 1);
                } else {
                    $size = (int) $matches[2];
                }
            }
            if ($matches[3]) {
                $sqlType = $row['Type'];
            }
            if (isset(static::$defaultTypeSizes[$nativeType]) && $size === static::$defaultTypeSizes[$nativeType]) {
                $size = null;
            }
        } elseif (preg_match('/^(\w+)\(/', $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($nativeType === 'enum') {
                $sqlType = $row['Type'];
            }
        } else {
            $nativeType = $row['Type'];
        }

        // BLOBs can't have any default values in MySQL
        $default = preg_match('~blob|text~', $nativeType) ? null : $row['Default'];

        $propelType = $this->getMappedPropelType($nativeType);
        if (!$propelType) {
            $propelType = Field::DEFAULT_TYPE;
            $sqlType = $row['Type'];
            $this->warn("Field [" . $entity->getFullTableName() . "." . $name. "] has a field type (".$nativeType.") that Propel does not support.");
        }

        // Special case for TINYINT(1) which is a BOOLEAN
        if (PropelTypes::TINYINT === $propelType && 1 === $size) {
            $propelType = PropelTypes::BOOLEAN;
        }

        $field = new Field($name);
        $field->setEntity($entity);
        $field->setDomainForType($propelType);
        if ($sqlType) {
            $field->getDomain()->replaceSqlType($sqlType);
        }
        $field->getDomain()->setSize($size);
        $field->getDomain()->replaceScale($scale);
        if ($default !== null) {
            if ($propelType == PropelTypes::BOOLEAN) {
                if ($default == '1') {
                    $default = 'true';
                }
                if ($default == '0') {
                    $default = 'false';
                }
            }
            if (in_array($default, ['CURRENT_TIMESTAMP'])) {
                $type = FieldDefaultValue::TYPE_EXPR;
            } else {
                $type = FieldDefaultValue::TYPE_VALUE;
            }
            $field->getDomain()->setDefaultValue(new FieldDefaultValue($default, $type));
        }
        $field->setAutoIncrement($autoincrement);
        $field->setNotNull(!$isNullable);

        if ($this->addVendorInfo) {
            $vi = $this->getNewVendorInfoObject($row);
            $field->addVendorInfo($vi);
        }

        return $field;
    }

    /**
     * Load foreign keys for this entity.
     */
    protected function addRelations(Entity $entity)
    {
        $database = $entity->getDatabase();

        $dataFetcher = $this->dbh->query(sprintf('SHOW CREATE TABLE %s', $this->getPlatform()->doQuoting($entity->getFullTableName())));
        $row = $dataFetcher->fetch();

        $Relations = []; // local store to avoid duplicates

        // Get the information on all the foreign keys
        $pattern = '/CONSTRAINT `([^`]+)` FOREIGN KEY \((.+)\) REFERENCES `([^\s]+)` \((.+)\)(.*)/';
        if (preg_match_all($pattern, $row[1], $matches)) {
            $tmpArray = array_keys($matches[0]);
            foreach ($tmpArray as $curKey) {
                $name    = $matches[1][$curKey];
                $rawlcol = $matches[2][$curKey];
                $ftbl    = str_replace('`', '', $matches[3][$curKey]);
                $rawfcol = $matches[4][$curKey];
                $fkey    = $matches[5][$curKey];

                $lcols = [];
                foreach (preg_split('/`, `/', $rawlcol) as $piece) {
                    $lcols[] = trim($piece, '` ');
                }

                $fcols = [];
                foreach (preg_split('/`, `/', $rawfcol) as $piece) {
                    $fcols[] = trim($piece, '` ');
                }

                // typical for mysql is RESTRICT
                $fkactions = [
                    'ON DELETE' => Model::RELATION_RESTRICT,
                    'ON UPDATE' => Model::RELATION_RESTRICT,
                ];

                if ($fkey) {
                    // split foreign key information -> search for ON DELETE and afterwords for ON UPDATE action
                    foreach (array_keys($fkactions) as $fkaction) {
                        $result = null;
                        preg_match('/' . $fkaction . ' (' . Model::RELATION_CASCADE . '|' . Model::RELATION_SETNULL . ')/', $fkey, $result);
                        if ($result && is_array($result) && isset($result[1])) {
                            $fkactions[$fkaction] = $result[1];
                        }
                    }
                }

                // restrict is the default
                foreach ($fkactions as $key => $action) {
                    if (Model::RELATION_RESTRICT === $action) {
                        $fkactions[$key] = null;
                    }
                }

                $localFields = [];
                $foreignFields = [];
                if ($entity->guessSchemaName() != $database->getSchema() && false == strpos($ftbl, $database->getPlatform()->getSchemaDelimiter())) {
                    $ftbl = $entity->guessSchemaName() . $database->getPlatform()->getSchemaDelimiter() . $ftbl;
                }

                $foreignEntity = $database->getEntityByTableName($ftbl);

                if (!$foreignEntity) {
                    continue;
                }

                foreach ($fcols as $fcol) {
                    $foreignFields[] = $foreignEntity->getField($fcol);
                }
                foreach ($lcols as $lcol) {
                    $localFields[] = $entity->getField($lcol);
                }

                if (!isset($Relations[$name])) {
                    $fk = new Relation($name);
                    $fk->setForeignEntityName($foreignEntity->getFullName());
                    $fk->setOnDelete($fkactions['ON DELETE']);
                    $fk->setOnUpdate($fkactions['ON UPDATE']);
                    $entity->addRelation($fk);
                    $Relations[$name] = $fk;
                }

                $max = count($localFields);
                for ($i = 0; $i < $max; $i++) {
                    $Relations[$name]->addReference($localFields[$i], $foreignFields[$i]);
                }
            }
        }
    }

    /**
     * Load indexes for this entity
     */
    protected function addIndexes(Entity $entity)
    {
        $stmt = $this->dbh->query(sprintf('SHOW INDEX FROM %s', $this->getPlatform()->doQuoting($entity->getFullTableName())));

        // Loop through the returned results, grouping the same key_name together
        // adding each field for that key.

        /** @var $indexes Index[] */
        $indexes = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $colName = $row['Column_name'];
            $colSize = $row['Sub_part'];
            $name = $row['Key_name'];

            if ('PRIMARY' === $name) {
                continue;
            }

            if (!isset($indexes[$name])) {
                $isUnique = (0 == $row['Non_unique']);
                if ($isUnique) {
                    $indexes[$name] = new Unique($name);
                } else {
                    $indexes[$name] = new Index($name);
                }
                if ($this->addVendorInfo) {
                    $vi = $this->getNewVendorInfoObject($row);
                    $indexes[$name]->addVendorInfo($vi);
                }
                $indexes[$name]->setEntity($entity);
            }

            $indexes[$name]->addField([
                'name' => $colName,
                'size' => $colSize
            ]);
        }

        foreach ($indexes as $index) {
            if ($index instanceof Unique) {
                $entity->addUnique($index);
            } else {
                $entity->addIndex($index);
            }
        }
    }

    /**
     * Loads the primary key for this entity.
     */
    protected function addPrimaryKey(Entity $entity)
    {
        $stmt = $this->dbh->query(sprintf('SHOW KEYS FROM %s', $this->getPlatform()->doQuoting($entity->getFullTableName())));

        // Loop through the returned results, grouping the same key_name together
        // adding each field for that key.
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // Skip any non-primary keys.
            if ('PRIMARY' !== $row['Key_name']) {
                continue;
            }
            $name = $row['Column_name'];
            $entity->getField($name)->setPrimaryKey(true);
        }
    }

    /**
     * Adds vendor-specific info for entity.
     *
     * @param Entity $entity
     */
    protected function addEntityVendorInfo(Entity $entity)
    {
        $stmt = $this->dbh->query("SHOW TABLE STATUS LIKE '" . $entity->getFullTableName() . "'");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$this->addVendorInfo) {
            // since we depend on `Engine` in the MysqlPlatform, we always have to extract this vendor information
            $row = ['Engine' => $row['Engine']];
        }
        $vi = $this->getNewVendorInfoObject($row);
        $entity->addVendorInfo($vi);
    }
}
