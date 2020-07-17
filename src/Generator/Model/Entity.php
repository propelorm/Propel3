<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\Set;
use phootwork\lang\Text;
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
 * @author Cristiano Cinotti
 */
class Entity
{
    use ActiveRecordPart, BehaviorPart, DatabasePart, DescriptionPart, FieldsPart, GeneratorPart, NamespacePart,
        PlatformAccessorPart, SchemaNamePart, ScopePart, SqlPart, SuperordinatePart, VendorPart;

    //
    // Model properties
    // ------------------------------------------------------------
    private Text $tableName;
    private Text $alias;

    //
    // References to other models
    // ------------------------------------------------------------

    /** @var bool|string */
    private string $repository;
    private Field $inheritanceField;

    //
    // Collections to other models
    // ------------------------------------------------------------
    private Set $relations;
    private Set $referrers;
    private Set $indices;
    private Set $unices;

    //
    // Database related options/properties
    // ------------------------------------------------------------
    private bool $allowPkInsert = false;
    private bool $containsForeignPK = false;

    /**
     * Whether this entity is an implementation detail. Implementation details are entities that are only
     * relevant in the current persister api, like implicit pivot tables in n-n relations, or foreign key columns.
     * @var bool
     */
    private bool $implementationDetail = false;
    private bool $needsTransactionInPostgres = false;
    private bool $forReferenceOnly = false;
    private bool $reloadOnInsert = false;
    private bool $reloadOnUpdate = false;

    //
    // Generator options
    // ------------------------------------------------------------
    private bool $readOnly = false;
    private bool $isAbstract = false;
    private bool $skipSql = false;

    /**
     * @TODO maybe move this to database related options/props section ;)
     *
     * @var bool
     */
    private bool $isCrossRef = false;


    /**
     * Constructs a entity object with a name
     *
     * @param string $name entity name
     */
    public function __construct(string $name = null)
    {
        if ($name) {
            $this->setName($name);
        }

        // init
        $this->alias = new Text();
        $this->relations = new Set();
        $this->indices = new Set();
        $this->referrers = new Set();
        $this->tableName = new Text();
        $this->unices = new Set();
        $this->initFields();
        $this->initBehaviors();
        $this->initSql();
        $this->initVendor();
    }

    /**
     * @inheritdoc
     * @return Database|null
     */
    protected function getSuperordinate(): ?Database
    {
        return $this->getDatabase();
    }

    //
    // Model properties
    // ------------------------------------------------------------

    /**
     * @param string|Text $tableName
     */
    public function setTableName($tableName): void
    {
        $this->tableName = new Text($tableName);
    }

    /**
     * Returns the blank table name.
     *
     * @return Text
     */
    public function getTableName(): Text
    {
        if ($this->tableName->isEmpty()) {
            $this->setTableName($this->getName()->toSnakeCase());
        }

        return $this->tableName;
    }

    /**
     * The table name with database scope.
     *
     * @return Text
     */
    public function getScopedTableName(): Text
    {
        return $this->getTableName()->prepend($this->getScope());
    }

    /**
     * Returns the scoped table name with possible schema.
     *
     * @return Text
     */
    public function getFullTableName(): Text
    {
        $delimiter = $this->getPlatform()->getSchemaDelimiter();

        return $this->getScopedTableName()
            ->prepend($delimiter)
            ->prepend($this->getSchemaName())
            ->trimStart($delimiter) //if the schemaName is '', the delimiter is the first character and must be removed
        ;
    }

    protected function getEntity(): self
    {
        return $this;
    }

    //
    // References to other models
    // ------------------------------------------------------------

    /**
     * @param string $repository
     */
    public function setRepository(string $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * @return string
     */
    public function getRepository(): string
    {
        return $this->repository;
    }

    /**
     * Set the database that contains this entity.
     *
     * @param Database $database
     */
    public function setDatabase(Database $database): void
    {
        if (isset($this->database) && $this->database !== $database) {
            $this->database->removeEntity($this);
        }
        $this->database = $database;
        $this->database->addEntity($this);
    }

    /**
     * Returns the Database platform.
     *
     * @return PlatformInterface
     */
    public function getPlatform(): ?PlatformInterface
    {
        return isset($this->database) ? $this->database->getPlatform() : null;
    }

    /**
     * Returns the field that subclasses the class representing this
     * entity can be produced from.
     *
     * @return Field
     */
    public function getChildrenField(): ?Field
    {
        return $this->inheritanceField ?? null;
    }

    /**
     * Returns the subclasses that can be created from this entity.
     *
     * @return string[] Array of subclasses  names
     */
    public function getChildrenNames(): array
    {
        if (!isset($this->inheritanceField) || !$this->inheritanceField->isEnumeratedClasses()) {
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

    protected function registerBehavior(Behavior $behavior): void
    {
        $behavior->setEntity($this);
    }

    protected function unregisterBehavior(Behavior $behavior): void
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
     */
    public function addField(Field $field): void
    {
        //The field must be unique
        if ($this->hasFieldByName($field->getName()->toString())) {
            throw new EngineException("Field `{$field->getName()}` declared twice in entity `{$this->getName()}`");
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
    }

    /**
     * Returns the number of fields in this entity.
     *
     * @return int
     */
    public function countFields(): int
    {
        return $this->fields->size();
    }

    /**
     * @deprecated Use `Entity::countFields()` instead
     * @return int
     */
    public function getNumFields(): int
    {
        return $this->countFields();
    }


    /**
     * Returns the number of lazy loaded fields in this entity.
     *
     * @return int
     */
    public function countLazyLoadFields(): int
    {
        return $this->fields->findAll(fn(Field $element): bool => $element->isLazyLoad())->count();
    }

    /**
     * @deprecated Use `Entity::countLazyLoadFields()` instead
     * @return int
     */
    public function getNumLazyLoadFields(): int
    {
        return $this->countLazyLoadFields();
    }


    /**
     * Returns whether or not one of the fields is of type ENUM.
     *
     * @return bool
     */
    public function hasEnumFields(): bool
    {
        return $this->fields->search(fn(Field $element): bool => $element->isEnumType());
    }

    // relations
    // -----------------------------------------

    /**
     * Adds a new relation to this entity.
     *
     * @param Relation $relation The relation
     */
    public function addRelation(Relation $relation): void
    {
        $relation->setEntity($this);
        $this->relations->add($relation);
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
        return $this->getCrossRelations()->count() > 0;
    }

    /**
     * @param string $fieldName
     *
     * @return Relation
     */
    public function getRelation(string $fieldName): Relation
    {
        return $this->relations->find($fieldName,
            fn(Relation $element, string $query): bool => $element->getName()->toString() === $query
        );
    }

    /**
     * Returns the list of all foreign keys.
     *
     * @return Set
     */
    public function getRelations(): Set
    {
        return $this->relations;
    }

    /**
     * Returns all foreign keys from this entity that reference the entity passed
     * in argument.
     *
     * @param string $entityName
     *
     * @return Set
     */
    public function getRelationsReferencingEntity(string $entityName): Set
    {
        return $this->relations->findAll($entityName,
            fn(Relation $relation, string $query): bool => $relation->getForeignEntityName() === $entityName
        );
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
     * @return Set
     */
    public function getFieldRelations(string $fieldName): Set
    {
        return $this->relations->findAll($fieldName,
            fn(Relation $relation, string $query): bool => $relation->getLocalFields()->contains($fieldName)
        );
    }

    /**
     * Returns the list of cross relations.
     */
    public function getCrossRelations(): Set
    {
        return $this->referrers
            ->filter(fn(Relation $element): bool => $element->getEntity()->isCrossRef())
            ->map(function(Relation $refRelation): CrossRelation {
                $crossRelation = new CrossRelation($refRelation, $this);
                foreach ($refRelation->getOtherFks() as $relation) {
                    if ($relation->isAtLeastOneLocalPrimaryKeyIsRequired() &&
                        $crossRelation->isAtLeastOneLocalPrimaryKeyNotCovered($relation)) {
                        $crossRelation->addRelation($relation);
                    }
                }

                return $crossRelation;
            })
            ->filter(fn(CrossRelation $element): bool => $element->hasRelations())
            ;
    }

    /**
     * Returns the list of entities referenced by foreign keys in this entity.
     *
     * @return Set
     */
    public function getForeignEntityNames(): Set
    {
        return $this->relations->map(fn(Relation $element): string => $element->getForeignEntityName());
    }


    // referrer
    // -----------------------------------------

    /**
     * Adds the foreign key from another entity that refers to this entity.
     *
     * @param Relation $relation
     */
    public function addReferrer(Relation $relation): void
    {
        $this->referrers->add($relation);
    }

    /**
     * Returns the list of references to this entity.
     *
     * @return Set
     */
    public function getReferrers(): Set
    {
        return $this->referrers;
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
        $index->addFields($fields);

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
     */
    public function addIndex(Index $index): void
    {
        if ($this->hasIndex($index->getName()->toString())) {
            throw new \InvalidArgumentException(sprintf('Index "%s" already exist.', $index->getName()));
        }

        if ($index->getFields()->size() === 0) {
            throw new \InvalidArgumentException(sprintf('Index "%s" has no fields.', $index->getName()));
        }

        $index->setEntity($this);
        // @TODO $index->getName() ? we can do better here. Under investigation
        $index->getName(); // we call this method so that the name is created now if it doesn't already exist.
        $this->indices->add($index);
    }

    /**
     * Checks if the entity has a index by name.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function hasIndex(string $name): bool
    {
        return $this->indices->search($name, fn(Index $elem, string $query): bool => $elem->getName()->toString() === $name);
    }

    /**
     * Checks if a index exists with the given $keys.
     *
     * @param array $keys
     * @return bool
     */
    public function isIndex(array $keys): bool
    {
        /** @var Index $index */
        foreach ($this->indices as $index) {
            if (count($keys) === $index->getFields()->size()) {
                $allAvailable = true;
                foreach ($keys as $key) {
                    if (!$index->hasFieldByName($key instanceof Field ? $key->getName()->toString() : $key)) {
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
     * @return Set
     */
    public function getIndices(): Set
    {
        return $this->indices;
    }

    /**
     * Removes an index off this entity
     *
     * @param Index|string $index
     */
    public function removeIndex($index): void
    {
        if (is_string($index)) {
            $index = $this->indices->find($index,
                fn(Index $index, string $query): bool => $index->getName()->toString() === $query
            );
        }

        if ($index instanceof Index && $index->getEntity() === $this) {
            $index->setEntity(null);
            $this->indices->remove($index);
        }
    }


    // unices
    // -----------------------------------------

    /**
     * Adds a new Unique index to the list of unique indices and set the
     * parent entity of the field to the current entity.
     *
     * @param Unique $unique
     */
    public function addUnique(Unique $unique): void
    {
        $unique->setEntity($this);
        $unique->getName(); // we call this method so that the name is created now if it doesn't already exist.

        $this->unices->add($unique);
    }

    /**
     * Checks if $keys are a unique constraint in the entity.
     * (through primaryKey, through a regular unices constraints or for single keys when it has isUnique=true)
     *
     * @param Field[]|string[] $keys
     * @return bool
     * @throws \InvalidArgumentException If a field is not associated to this entity
     */
    public function isUnique(array $keys): bool
    {
        if (1 === count($keys)) {
            $field = $keys[0] instanceof Field ? $keys[0] : $this->getFieldByName($keys[0]);
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
            foreach ($this->getPrimaryKey() as $pk) {
                if (is_string($keys[0])) {
                    if (!in_array((string) $pk->getName(), $keys)) {
                        $allPk = false;
                        break;
                    }
                } else {
                    if (!in_array($pk, $keys, true)) {
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
            if (count($unique->getFields()->toArray()) === count($keys)) {
                $allAvailable = true;
                foreach ($keys as $key) {
                    if (!$unique->hasFieldByName($key instanceof Field ? (string) $key->getName() : $key)) {
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
     * @return Set
     */
    public function getUnices(): Set
    {
        return $this->unices;
    }

    /**
     * Removes an unique index off this entity
     *
     * @param Unique|string $unique
     */
    public function removeUnique($unique): void
    {
        if (is_string($unique)) {
            $unique = $this->unices->find($unique,
                fn(Unique $index, string $query): bool => $index->getName()->toString() === $query
            );
        }

        if ($unique instanceof Unique && $unique->getEntity() === $this) {
            $unique->setEntity(null);
            $this->unices->remove($unique);
        }
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
    public function setImplementationDetail(bool $implementationDetail): void
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

    //@todo useful?
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
     * Makes this database reload on insert statement.
     *
     * @param bool $flag True by default
     */
    public function setReloadOnInsert(bool $flag = true): void
    {
        $this->reloadOnInsert = $flag;
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
     */
    public function setReloadOnUpdate(bool $flag = true): void
    {
        $this->reloadOnUpdate = $flag;
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
     */
    public function setForReferenceOnly(bool $flag = true): void
    {
        $this->forReferenceOnly = $flag;
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
     */
    public function setSkipSql(bool $skip): void
    {
        $this->skipSql = $skip;
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
        return $this->fields->filter(fn(Field $field): bool => $field->isPrimaryKey())->toArray();
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
     * Useful for entities with a PK using a single field.
     *
     * @return Field
     */
    public function getFirstPrimaryKeyField(): ?Field
    {
        return $this->fields->find(fn(Field $elem): bool => $elem->isPrimaryKey());
    }

    /**
     * Sets whether or not this entity contains a foreign primary key.
     *
     * @param $containsForeignPK
     */
    public function setContainsForeignPK(bool $containsForeignPK): void
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
     * @todo never used: remove?
     *
     * Returns all required(notNull && no defaultValue) primary keys which are not in $primaryKeys.
     *
     * @param Field[] $primaryKeys
     * @return Field[]
     *//*
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
    }*/

    /**
     * Returns whether or not this entity has any auto-increment primary keys.
     *
     * @return bool
     */
    public function hasAutoIncrementPrimaryKey(): bool
    {
        return null !== $this->getAutoIncrementPrimaryKey();
    }

    public function getAutoIncrementFieldNames(): Set
    {
        return $this->fields
            ->filter(fn(Field $elem): bool => $elem->isAutoIncrement())
            ->map(fn(Field $elem): Text => $elem->getName())
            ;
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
        return $this->fields->search(fn(Field $elem): bool => $elem->isAutoIncrement());
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
     */
    public function setReadOnly(bool $flag = true): void
    {
        $this->readOnly = $flag;
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
     */
    public function setAbstract(bool $flag = true): void
    {
        $this->isAbstract = $flag;
    }

    /**
     * Sets a cross reference status for this foreign key.
     *
     * @param bool $flag
     */
    public function setCrossRef(bool $flag = true): void
    {
        $this->isCrossRef = $flag;
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
     * Returns whether or not the entity behaviors offer additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders(): bool
    {
        return $this->behaviors->search(fn(Behavior $elem): bool => $elem->hasAdditionalBuilders());
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

    //
    // MISC
    // --------------

    /**
     * Returns the schema name from this entity or from its database.
     * @deprecated Use `getSchemaName()` instead
     * @return Text
     */
    public function guessSchemaName(): Text
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
        return $this->getDatabase()
        && $this->database->getSchema()
        && ($platform = $this->getPlatform())
        && $platform->supportsSchemas();
    }

    /**
     * Returns the PHP name of an active record object this entry references.
     *
     * @return Text
     */
    public function getAlias(): Text
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
        return !$this->alias->isEmpty();
    }

    /**
     * Sets whether or not this entity is specified in the schema or if there is
     * just a foreign key reference to it.
     *
     * @param string|Text $alias
     */
    public function setAlias($alias): void
    {
        $this->alias = new Text($alias);
    }
}
