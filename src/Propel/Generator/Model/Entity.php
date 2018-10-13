<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Parts\ActiveRecordPart;
use Propel\Generator\Model\Parts\BehaviorPart;
use Propel\Generator\Model\Parts\DatabasePart;
use Propel\Generator\Model\Parts\DescriptionPart;
use Propel\Generator\Model\Parts\FieldsPart;
use Propel\Generator\Model\Parts\GeneratorPart;
use Propel\Generator\Model\Parts\NamespacePart;
use Propel\Generator\Model\Parts\PlatformAccessorPart;
use Propel\Generator\Model\Parts\SchemaNamePart;
use Propel\Generator\Model\Parts\ScopePart;
use Propel\Generator\Model\Parts\SqlPart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Exception\RuntimeException;
use phootwork\collection\Set;

/**
 * Data about a entity used in an application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Entity
{
    use SuperordinatePart;
    use PlatformAccessorPart;
    use ActiveRecordPart;
    use ScopePart;
    use BehaviorPart;
    use NamespacePart;
    use SchemaNamePart;
    use SqlPart;
    use GeneratorPart;
    use VendorPart;
    use DatabasePart;
    use DescriptionPart;
    use FieldsPart;

    //
    // Model properties
    // ------------------------------------------------------------
    private $tableName;
    private $alias;



    //
    // References to other models
    // ------------------------------------------------------------

    /** @var bool|string */
    private $repository;

    /** @var Field */
    private $inheritanceField;



    //
    // Collections to other models
    // ------------------------------------------------------------

    /** @var Set */
    private $relations;

    /** @var Set */
    private $referrers;

    /** @var Set */
    private $foreignEntityNames;

    /** @var Set */
    private $indices;

    /** @var Set */
    private $unices;



    //
    // Database related options/properties
    // ------------------------------------------------------------

    /** @var bool */
    private $allowPkInsert;

    /** @var bool */
    private $containsForeignPK;

    /**
     * Whether this entity is an implementation detail. Implementation details are entities that are only
     * relevant in the current persister api, like implicit pivot tables in n-n relations, or foreign key columns.
     * @var bool
     */
    private $implementationDetail = false;

    /** @var bool */
    private $needsTransactionInPostgres;

    /** @var bool */
    private $forReferenceOnly;

    /** @var bool */
    private $reloadOnInsert;

    /** @var bool */
    private $reloadOnUpdate;


    //
    // Generator options
    // ------------------------------------------------------------

    /** @var bool */
    private $readOnly;

    /** @var bool */
    private $isAbstract;

    /** @var bool */
    private $skipSql;

    /**
     * @TODO maybe move this to database related options/props section ;)
     *
     * @var bool
     */
    private $isCrossRef;


    /**
     * Constructs a entity object with a name
     *
     * @param string $name entity name
     */
    public function __construct($name = null)
    {
        if ($name) {
            $this->setName($name);
        }

        // init
        $this->fields = new Set();
        //$this->fieldsByName = new Map();
        //$this->fieldsByLowercaseName = new Map();
        $this->relations = new Set();
        $this->foreignEntityNames = new Set();
        $this->indices = new Set();
        $this->referrers = new Set();
        $this->unices = new Set();
        $this->initBehaviors();
        $this->initSql();
        $this->initVendor();

        // default values
        $this->allowPkInsert = false;
        $this->isAbstract = false;
        $this->isCrossRef = false;
        $this->readOnly = false;
        $this->reloadOnInsert = false;
        $this->reloadOnUpdate = false;
        $this->skipSql = false;
        $this->forReferenceOnly = false;
    }

    // @TODO it's todo
    public function __clone()
    {
        $fields = [];
        if ($this->fields) {
            foreach ($this->fields as $oldCol) {
                $col = clone $oldCol;
                $fields[] = $col;
          //      $this->fieldsByName[$col->getName()] = $col;
          //      $this->fieldsByLowercaseName[strtolower($col->getName())] = $col;
                //            $this->fieldsByPhpName[$col->getName()] = $col;
            }
            $this->fields = $fields;
        }
    }

    /**
     * @inheritdoc
     * @return Database
     */
    protected function getSuperordinate(): ?Database
    {
        return $this->database;
    }

    //
    // Model properties
    // ------------------------------------------------------------

    /**
     * @param string $tableName
     * @return $this
     */
    public function setTableName(string $tableName): Entity
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Returns the blank table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        $tableName = !$this->tableName ? NamingTool::toSnakeCase($this->name) : $this->tableName;

        return $tableName;
    }

    /**
     * The table name with database scope.
     *
     * @return string
     */
    public function getScopedTableName(): string
    {
        $tableName = !$this->tableName ? NamingTool::toSnakeCase($this->name) : $this->tableName;
        $scope = $this->getScope();

        if ($scope) {
            $tableName = $scope . $tableName;
        }

        return $tableName;
    }

    /**
     * Returns the scoped table name with possible schema.
     *
     * @return string
     */
    public function getFullTableName(): string
    {
        $fqTableName = $this->getScopedTableName();

        if ($this->hasSchema()) {
            $fqTableName = $this->guessSchemaName() . $this->getPlatform()->getSchemaDelimiter() . $fqTableName;
        }

        return $fqTableName;
    }

    //
    // References to other models
    // ------------------------------------------------------------

    /**
     * @param bool|string $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return bool|string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set the database that contains this entity.
     *
     * @param Database $database
     * @return $this
     */
    public function setDatabase(Database $database): Entity
    {
        if ($this->database !== null && $this->database !== $database) {
            $this->database->removeEntity($this);
        }
        $this->database = $database;
        $this->database->addEntity($this);

        return $this;
    }

    /**
     * Returns the Database platform.
     *
     * @return PlatformInterface
     */
    public function getPlatform(): ?PlatformInterface
    {
        return $this->database ? $this->database->getPlatform() : null;
    }

    /**
     * Returns the field that subclasses the class representing this
     * entity can be produced from.
     *
     * @return null|Field
     */
    public function getChildrenField(): ?Field
    {
        return $this->inheritanceField;
    }

    /**
     * Returns the subclasses that can be created from this entity.
     *
     * @return string[] Array of subclasses  names
     */
    public function getChildrenNames(): array
    {
        if (null === $this->inheritanceField || !$this->inheritanceField->isEnumeratedClasses()) {
            return [];
        }

        $names = [];
        foreach ($this->inheritanceField->getChildren() as $child) {
            $names[] = get_class($child);
        }

        return $names;
    }



    //
    // Collections to other models
    // ------------------------------------------------------------


    // behaviors
    // -----------------------------------------

    /**
     * @TODO can it be externalized?
     *
     * Executes behavior entity modifiers.
     * This is only for testing purposes. Model\Database calls already `modifyEntity` on each behavior.
     */
    public function applyBehaviors()
    {
        foreach ($this->behaviors as $behavior) {
            if (!$behavior->isEntityModified()) {
                $behavior->getEntityModifier()->modifyEntity();
                $behavior->setEntityModified(true);
            }
        }
    }

    protected function registerBehavior(Behavior $behavior)
    {
        $behavior->setEntity($this);
    }

    protected function unregisterBehavior(Behavior $behavior)
    {
        $behavior->setEntity(null);
    }


    // fields
    // -----------------------------------------

    /**
     * Adds a new field to the entity.
     *
     * @param Field $field
     *
     * @throws EngineException
     * @return $this
     */
    public function addField(Field $field): Entity
    {
        //The field must be unique
        if (null !== $this->getFieldByName($field->getName())) {
            throw new EngineException(sprintf('Field "%s" declared twice in entity "%s"', $field->getName(), $this->getName()));
        }

        $field->setEntity($this);
        $this->fields->add($field);

        $field->setPosition($this->fields->size());

        if ($field->requiresTransactionInPostgres()) {
            $this->needsTransactionInPostgres = true;
        }

        if ($field->isInheritance()) {
            $this->inheritanceField = $field;
        }

        return $this;
    }

//    /**
//     * MOVED into SqlDefaultPlatform class
//     *
//     * Returns a delimiter-delimited string list of field names.
//     *
//     * @see SqlDefaultPlatform::getFieldList() if quoting is required
//     *
//     * @param array
//     * @param string $delimiter
//     * @return string
//     */
//    public function getFieldList(array $columns, string $delimiter = ','): string
//    {
//        $list = [];
//        foreach ($columns as $col) {
//            if ($col instanceof Field) {
//                $col = $col->getName();
//            }
//            $list[] = $col;
//        }
//        return implode($delimiter, $list);
//    }

    /**
     * @TODO check consistency with naming size/num/count methods
     *
     * Returns the number of fields in this entity.
     *
     * @return int
     */
    public function getNumFields(): int
    {
        return $this->fields->size();
    }

    /**
     * @TODO check consistency with naming size/num/count methods
     *
     * Returns the number of lazy loaded fields in this entity.
     *
     * @return int
     */
    public function getNumLazyLoadFields(): int
    {
        $count = 0;
        foreach ($this->fields as $col) {
            if ($col->isLazyLoad()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns whether or not one of the fields is of type ENUM.
     *
     * @return bool
     */
    public function hasEnumFields(): bool
    {
        foreach ($this->fields as $col) {
            if ($col->isEnumType()) {
                return true;
            }
        }

        return false;
    }

//    Never used: remove?
//
//    private function getFieldPosition(Field $field): int
//    {
//        return $this->fields->indexOf($field);
//    }


    // relations
    // -----------------------------------------

    /**
     * Adds a new relation to this entity.
     *
     * @param Relation $relation The relation
     *
     * @return $this
     */
    public function addRelation(Relation $relation): Entity
    {
        $relation->setEntity($this);

        $this->relations->add($relation);
        $this->foreignEntityNames->add($relation->getForeignEntityName());

        return $this;

        if ($data instanceof Relation) {
            $relation = $data;
            $relation->setEntity($this);
            $this->relations[] = $relation;

            if (!in_array($relation->getForeignEntityName(), $this->foreignEntityNames)) {
                $this->foreignEntityNames[] = $relation->getForeignEntityName();
            }

            return $relation;
        }

        $relation = new Relation();
        $relation->setEntity($this);
        $relation->loadMapping($data);

        return $this->addRelation($relation);
    }

    /**
     * Adds several foreign keys at once.
     *
     * @param Relation[] $relations An array of Relation objects
     */
    public function addRelations(array $relations)
    {
        foreach ($relations as $relation) {
            $this->addRelation($relation);
        }
    }

    /**
     * Returns whether or not the entity has foreign keys.
     *
     * @return bool
     */
    public function hasRelations(): bool
    {
        return $this->relations->size() > 0;
    }

    /**
     * Returns whether the entity has cross foreign keys or not.
     *
     * @return bool
     */
    public function hasCrossRelations(): bool
    {
        return count($this->getCrossRelations()) > 0;
    }

    /**
     * @param string $fieldName
     *
     * @return Relation
     */
    public function getRelation($fieldName): Relation
    {
        foreach ($this->relations as $relation) {
            if ($relation->getField() == $fieldName) {
                return $relation;
            }
        }
    }

    /**
     * Returns the list of all foreign keys.
     *
     * @return Relation[]
     */
    public function getRelations(): array
    {
        return $this->relations->toArray();
    }

    /**
     * Returns all foreign keys from this entity that reference the entity passed
     * in argument.
     *
     * @param string $entityName
     *
     * @return Relation[]
     */
    public function getRelationsReferencingEntity(string $entityName): array
    {
        return $this->relations->filter(function (Relation $relation) use ($entityName) {
            return $relation->getForeignEntityName() === $entityName;
        })->toArray();
    }

    /**
     * Returns the foreign keys that include $field in it's list of local
     * fields.
     *
     * Eg. Foreign key (a, b, c) references tbl(x, y, z) will be returned of $field
     * is either a, b or c.
     *
     * @param string $fieldName Name of the field
     *
     * @return Relation[]
     */
    public function getFieldRelations(string $fieldName): array
    {
        return $this->relations->filter(function (Relation $relation) use ($fieldName) {
            return in_array($fieldName, $relation->getLocalFields()->toArray());
        })->toArray();
    }

    /**
     * Returns the list of cross foreign keys.
     *
     * @return CrossRelation[]
     */
    public function getCrossRelations()
    {
        $crossFks = [];
        foreach ($this->referrers as $refRelation) {
            if ($refRelation->getEntity()->isCrossRef()) {
                $crossRelation = new CrossRelation($refRelation, $this);
                foreach ($refRelation->getOtherFks() as $relation) {
                    if ($relation->isAtLeastOneLocalPrimaryKeyIsRequired() && $crossRelation->isAtLeastOneLocalPrimaryKeyNotCovered($relation)) {
                        $crossRelation->addRelation($relation);
                    }
                }
                if ($crossRelation->hasRelations()) {
                    $crossFks[] = $crossRelation;
                }
            }
        }

        return $crossFks;
    }

    /**
     * Returns the list of entities referenced by foreign keys in this entity.
     *
     * @return Set
     */
    public function getForeignEntityNames(): Set
    {
        return $this->foreignEntityNames;
    }


    // referrer
    // -----------------------------------------

    /**
     * Adds the foreign key from another entity that refers to this entity.
     *
     * @param Relation $relation
     * @return $this
     */
    public function addReferrer(Relation $relation): Entity
    {
        $this->referrers->add($relation);
        return $this;
    }

    /**
     * Returns the list of references to this entity.
     *
     * @return Relation[]
     */
    public function getReferrers(): array
    {
        return $this->referrers->toArray();
    }


    // indices
    // -----------------------------------------

    /**
     * Creates a new index.
     *
     * @param string $name The index name
     * @param array $fields The list of fields to index
     *
     * @return Index  $index   The created index
     */
    public function createIndex(string $name, array $fields): Index
    {
        $index = new Index($name);
        $index->setFields($fields);
        $index->resetFieldsSize();

        $this->addIndex($index);

        return $index;
    }

    /**
     * Adds a new index to the indices list and set the
     * parent entity of the field to the current entity.
     *
     * @param  Index $index
     *
     * @throw  InvalidArgumentException
     *
     * @return $this
     */
    public function addIndex(Index $index): Entity
    {
        if ($this->hasIndex($index->getName())) {
            throw new \InvalidArgumentException(sprintf('Index "%s" already exist.', $index->getName()));
        }

        if ($index->getFields()->size() === 0) {
            throw new \InvalidArgumentException(sprintf('Index "%s" has no fields.', $index->getName()));
        }

        $index->setEntity($this);
        // @TODO $index->getName() ? we can do better here. Under investigation
//         $index->getName();
        $this->indices->add($index);

        return $this;

//         $idx = new Index();
//         $idx->loadMapping($index);
//         foreach ((array)@$index['fields'] as $field) {
//             $idx->addField($field);
//         }
    }

    /**
     * Checks if the entity has a index by name.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function hasIndex($name): bool
    {
        foreach ($this->indices as $idx) {
            if ($idx->getName() == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a index exists with the given $keys.
     *
     * @param array $keys
     * @return bool
     */
    public function isIndex(array $keys): bool
    {
        foreach ($this->indices as $index) {
            if (count($keys) === $index->getFields()->size()) {
                $allAvailable = true;
                foreach ($keys as $key) {
                    if (!$index->hasField($key instanceof Field ? $key->getName() : $key)) {
                        $allAvailable = false;
                        break;
                    }
                }
                if ($allAvailable) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the list of all indices of this entity.
     *
     * @return Index[]
     */
    public function getIndices(): array
    {
        return $this->indices->toArray();
    }

    /**
     * Removes an index off this entity
     *
     * @param Index|string $index
     * @return $this
     */
    public function removeIndex($index): Entity
    {
        if (is_string($index)) {
            $index = $this->indices->find($index, function (Index $index, $query) {
                return $index->getName() == $query;
            });
        }

        if ($index instanceof Index && $index->getEntity() == $this) {
            $index->setEntity(null);
            $this->indices->remove($index);
        }

        return $this;
    }


    // unices
    // -----------------------------------------

    /**
     * Adds a new Unique index to the list of unique indices and set the
     * parent entity of the field to the current entity.
     *
     * @param Unique $unique
     *
     * @return $this
     */
    public function addUnique(Unique $unique): Entity
    {
        $unique->setEntity($this);
        $unique->getName(); // we call this method so that the name is created now if it doesn't already exist.

        $this->unices->add($unique);

        return $this;
    }

    /**
     * Checks if $keys are a unique constraint in the entity.
     * (through primaryKey, through a regular unices constraints or for single keys when it has isUnique=true)
     *
     * @param Field[]|string[] $keys
     * @return bool
     */
    public function isUnique(array $keys): bool
    {
        if (1 === count($keys)) {
            $field = $keys[0] instanceof Field ? $keys[0] : $this->getField($keys[0]);
            if ($field) {
                if ($field->isUnique()) {
                    return true;
                }

                if ($field->isPrimaryKey() && 1 === count($field->getEntity()->getPrimaryKey())) {
                    return true;
                }
            }
        }

        // check if pk == $keys
        if (count($this->getPrimaryKey()) === count($keys)) {
            $allPk = true;
            $stringArray = is_string($keys[0]);
            foreach ($this->getPrimaryKey() as $pk) {
                if ($stringArray) {
                    if (!in_array($pk->getName(), $keys)) {
                        $allPk = false;
                        break;
                    }
                } else {
                    if (!in_array($pk, $keys)) {
                        $allPk = false;
                        break;
                    }
                }
            }

            if ($allPk) {
                return true;
            }
        }

        // check if there is a unique constrains that contains exactly the $keys
        foreach ($this->unices as $unique) {
            if (count($unique->getFields()) === count($keys)) {
                $allAvailable = true;
                foreach ($keys as $key) {
                    if (!$unique->hasField($key instanceof Field ? $key->getName() : $key)) {
                        $allAvailable = false;
                        break;
                    }
                }
                if ($allAvailable) {
                    return true;
                }
            } else {
                continue;
            }
        }

        return false;
    }

    /**
     * Returns the list of all unique indices of this entity.
     *
     * @return Unique[]
     */
    public function getUnices(): array
    {
        return $this->unices->toArray();
    }

    /**
     * Removes an unique index off this entity
     *
     * @param Unique $unique
     * @return $this
     */
    public function removeUnique(Unique $unique): Entity
    {
        if ($unique->getEntity() == $this) {
            $unique->setEntity(null);
            $this->unices->remove($unique);
        }

        return $this;
    }



    //
    // Database related options/properties
    // ------------------------------------------------------------

    /**
     * @return bool
     */
    public function isImplementationDetail(): bool
    {
        return $this->implementationDetail;
    }

    /**
     * @param bool $implementationDetail
     */
    public function setImplementationDetail(bool $implementationDetail)
    {
        $this->implementationDetail = $implementationDetail;
    }

    /**
     * Return true if the field requires a transaction in Postgres.
     *
     * @return bool
     */
    public function requiresTransactionInPostgres(): bool
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * @param bool $identifierQuoting
     * @return $this
     */
    public function setIdentifierQuoting(bool $identifierQuoting): Entity
    {
        $this->identifierQuoting = $identifierQuoting;
        return $this;
    }

    /**
     * Checks if identifierQuoting is enabled. Looks up to its database->isIdentifierQuotingEnabled
     * if identifierQuoting is null hence undefined.
     *
     * Use getIdentifierQuoting() if you need the raw value.
     *
     * @return bool
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        return (null !== $this->identifierQuoting || !$this->database)
            ? $this->identifierQuoting
            : $this->database->isIdentifierQuotingEnabled();
    }

    /**
     * Quotes a identifier depending on identifierQuotingEnabled.
     *
     * Needs a platform assigned to its database.
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        if (!$this->getPlatform()) {
            throw new RuntimeException(
                'No platform specified. Can not quote without knowing which platform this entity\'s database is using.'
               );
        }

        if ($this->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * @return bool|null
     */
    public function getIdentifierQuoting(): ?bool
    {
        return $this->identifierQuoting;
    }

    /**
     * Makes this database reload on insert statement.
     *
     * @param bool $flag True by default
     * @return $this
     */
    public function setReloadOnInsert(bool $flag = true): Entity
    {
        $this->reloadOnInsert = $flag;
        return $this;
    }

    /**
     * Whether to force object to reload on INSERT.
     *
     * @return bool
     */
    public function isReloadOnInsert(): bool
    {
        return $this->reloadOnInsert;
    }

    /**
     * Makes this database reload on update statement.
     *
     * @param bool $flag True by default
     * @return $this
     */
    public function setReloadOnUpdate(bool $flag = true): Entity
    {
        $this->reloadOnUpdate = $flag;

        return $this;
    }

    /**
     * Returns whether or not to force object to reload on UPDATE.
     *
     * @return bool
     */
    public function isReloadOnUpdate(): bool
    {
        return $this->reloadOnUpdate;
    }

    /**
     * Returns whether or not to determine if code/sql gets created for this entity.
     * Entity will be skipped, if set to true.
     *
     * @param bool $flag
     * @return $this
     */
    public function setForReferenceOnly(bool $flag = true): Entity
    {
        $this->forReferenceOnly = $flag;
        return $this;
    }

    /**
     * Returns whether or not code and SQL must be created for this entity.
     *
     * Entity will be skipped, if return true.
     *
     * @return bool
     */
    public function isForReferenceOnly(): bool
    {
        return $this->forReferenceOnly;
    }

    /**
     * Returns whether we allow to insert primary keys on entitys with
     * native id method.
     *
     * @return bool
     */
    public function isAllowPkInsert(): bool
    {
        return $this->allowPkInsert;
    }

    /**
     * Returns whether or not Propel has to skip DDL SQL generation for this
     * entity (in the event it should not be created from scratch).
     *
     * @return bool
     */
    public function isSkipSql(): bool
    {
        return ($this->skipSql || $this->isAlias() || $this->isForReferenceOnly());
    }

    /**
     * Sets whether or not this entity should have its SQL DDL code generated.
     *
     * @param bool $skip
     * @return $this
     */
    public function setSkipSql(bool $skip): Entity
    {
        $this->skipSql = $skip;
        return $this;
    }


    // relations / key handling
    // -----------------------------------------

    /**
     * Returns the collection of Fields which make up the single primary
     * key for this entity.
     *
     * @return Field[]
     */
    public function getPrimaryKey(): array
    {
        return $this->fields->filter(function (Field $field) {
            return $field->isPrimaryKey();
        })->toArray();
    }

    /**
     * Returns whether or not this entity has a primary key.
     *
     * @return bool
     */
    public function hasPrimaryKey(): bool
    {
        return count($this->getPrimaryKey()) > 0;
    }

    /**
     * Returns whether or not this entity has a composite primary key.
     *
     * @return bool
     */
    public function hasCompositePrimaryKey(): bool
    {
        return count($this->getPrimaryKey()) > 1;
    }

    /**
     * Returns the first primary key field.
     *
     * Useful for entitys with a PK using a single field.
     *
     * @return Field
     */
    public function getFirstPrimaryKeyField(): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->isPrimaryKey()) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Sets whether or not this entity contains a foreign primary key.
     *
     * @param $containsForeignPK
     *
     * @return bool
     */
    public function setContainsForeignPK(bool $containsForeignPK)
    {
        $this->containsForeignPK = $containsForeignPK;
    }

    /**
     * Returns whether or not this entity contains a foreign primary key.
     *
     * @return bool
     */
    public function getContainsForeignPK(): bool
    {
        return $this->containsForeignPK;
    }

    /**
     * Returns all required(notNull && no defaultValue) primary keys which are not in $primaryKeys.
     *
     * @param Field[] $primaryKeys
     * @return Field[]
     */
    public function getOtherRequiredPrimaryKeys(array $primaryKeys): array
    {
        $pks = [];
        foreach ($this->getPrimaryKey() as $primaryKey) {
            if ($primaryKey->isNotNull() && !$primaryKey->hasDefaultValue()
                && !in_array($primaryKey, $primaryKeys, true)) {
                $pks = $primaryKey;
            }
        }

        return $pks;
    }

    /**
     * Returns whether or not this entity has any auto-increment primary keys.
     *
     * @return bool
     */
    public function hasAutoIncrementPrimaryKey(): bool
    {
        return null !== $this->getAutoIncrementPrimaryKey();
    }

    /**
     * @return string[]
     */
    public function getAutoIncrementFieldNames(): array
    {
        $names = [];
        foreach ($this->getFields() as $field) {
            if ($field->isAutoIncrement()) {
                $names[] = $field->getName();
            }
        }

        return $names;
    }

    /**
     * Returns the auto incremented primary key.
     *
     * @return Field
     */
    public function getAutoIncrementPrimaryKey(): ?Field
    {
        if (Model::ID_METHOD_NONE !== $this->getIdMethod()) {
            foreach ($this->getPrimaryKey() as $pk) {
                if ($pk->isAutoIncrement()) {
                    return $pk;
                }
            }
        }

        return null;
    }

    /**
     * Returns whether or not this entity has at least one auto increment field.
     *
     * @return bool
     */
    public function hasAutoIncrement(): bool
    {
        foreach ($this->getFields() as $field) {
            if ($field->isAutoIncrement()) {
                return true;
            }
        }

        return false;
    }



    //
    // Generator options
    // ------------------------------------------------------------

    /**
     * Returns whether or not this entity is read-only. If yes, only only
     * accessors and relationship accessors and mutators will be generated.
     *
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Makes this database in read-only mode.
     *
     * @param bool $flag True by default
     * @return $this
     */
    public function setReadOnly(bool $flag = true): Entity
    {
        $this->readOnly = $flag;
        return $this;
    }


    /**
     * Returns whether or not a entity is abstract, it marks the business object
     * class that is generated as being abstract. If you have a entity called
     * "FOO", then the Foo business object class will be declared abstract. This
     * helps support class hierarchies
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * Sets whether or not a entity is abstract, it marks the business object
     * class that is generated as being abstract. If you have a
     * entity called "FOO", then the Foo business object class will be
     * declared abstract. This helps support class hierarchies
     *
     * @param bool $flag
     * @return $this
     */
    public function setAbstract(bool $flag = true): Entity
    {
        $this->isAbstract = $flag;
        return $this;
    }

    /**
     * Sets a cross reference status for this foreign key.
     *
     * @param bool $flag
     * @return $this
     */
    public function setCrossRef(bool $flag = true): Entity
    {
        $this->isCrossRef = $flag;
        return $this;
    }

    /**
     * Alias for Entity::setCrossRef.
     *
     * @see Entity::setCrossRef
     *
     * @param bool $flag
     * @return $this;
     */
    public function setIsCrossRef(bool $flag = true): Entity
    {
        return $this->setCrossRef($flag);
    }

    /**
     * Returns whether or not there is a cross reference status for this foreign
     * key.
     *
     * @return bool
     */
    public function isCrossRef(): bool
    {
        return $this->isCrossRef;
    }

    /**
     * Alias for Entity::getIsCrossRef.
     *
     * @see Entity::isCrossRef
     *
     * @return bool
     */
    public function getIsCrossRef(): bool
    {
        return $this->isCrossRef;
    }

    /**
     * Returns whether or not the entity behaviors offer additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders(): bool
    {
        foreach ($this->behaviors as $behavior) {
            if ($behavior->hasAdditionalBuilders()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of additional builders provided by the entity behaviors.
     *
     * @return array
     */
    public function getAdditionalBuilders(): array
    {
        $additionalBuilders = [];
        foreach ($this->behaviors as $behavior) {
            $additionalBuilders = array_merge($additionalBuilders, $behavior->getAdditionalBuilders());
        }

        return $additionalBuilders;
    }

    /**
     * Get the early entity behaviors
     *
     * @return Array of Behavior objects
     */
    public function getEarlyBehaviors(): array
    {
        $behaviors = [];
        foreach ($this->behaviors as $name => $behavior) {
            if ($behavior->isEarly()) {
                $behaviors[$name] = $behavior;
            }
        }

        return $behaviors;
    }







    //
    // MISC
    // --------------

    /**
     * Returns the schema name from this entity or from its database.
     *
     * @return string
     */
    public function guessSchemaName(): string
    {
        if (null === $this->schemaName) {
            return $this->database->getSchema()->getName();
        }

        return $this->schemaName;
    }

    /**
     * Returns whether or not this entity is linked to a schema.
     *
     * @return bool
     */
    public function hasSchema(): bool
    {
        return $this->database
        && ($this->database->getSchema() ?: $this->database->getSchema())
        && ($platform = $this->getPlatform())
        && $platform->supportsSchemas();
    }

    /**
     * Returns the PHP name of an active record object this entry references.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Returns whether or not this entity is specified in the schema or if there
     * is just a foreign key reference to it.
     *
     * @return bool
     */
    public function isAlias(): bool
    {
        return null !== $this->alias;
    }

    /**
     * Sets whether or not this entity is specified in the schema or if there is
     * just a foreign key reference to it.
     *
     * @param string $alias
     * @return $this
     */
    public function setAlias(string $alias): Entity
    {
        $this->alias = $alias;
        return $this;
    }
}
