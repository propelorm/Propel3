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

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Parts\BehaviorPart;
use Propel\Generator\Model\Parts\NamespacePart;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Exception\RuntimeException;
use phootwork\collection\Set;
use phootwork\collection\Map;
use Propel\Generator\Config\GeneratorConfigInterface;

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
    use BehaviorPart;
    use NamespacePart;



    //
    // Model properties
    // ------------------------------------------------------------
    private $name;
    private $tableName;
    private $description;

    private $alias;



    //
    // References to other models
    // ------------------------------------------------------------

    /** @var Database */
    private $database;

    /**
     * RepositoryClass
     *
     * @var bool|string
     */
    private $repository;

    /** @var Field */
    private $inheritanceField;



    //
    // Collections to other models
    // ------------------------------------------------------------

    /** @var Set */
    private $fields;

    /** @var Map */
    private $fieldsByName;

    /** @var Map */
    private $fieldsByLowercaseName;

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

    private $idMethod;

    /** @var Set */
    private $idMethodParameters;

    /** @var bool */
    private $allowPkInsert;

    private $containsForeignPK;

    /**
     * Whether this entity is an implementation detail. Implementation details are entities that are only
     * relevant in the current persister api, like implicit pivot tables in n-n relations, or foreign key columns.
     * @var bool
     */
    private $implementationDetail = false;

    private $needsTransactionInPostgres;

    /**
     * @var bool
     */
    private $heavyIndexing;

    /**
     * @var bool
     */
    private $identifierQuoting;


    private $forReferenceOnly;
    private $reloadOnInsert;
    private $reloadOnUpdate;

    private $defaultStringFormat;



    //
    // Generator options
    // ------------------------------------------------------------

    /**
     * @var bool|null
     */
    private $activeRecord;


    private $readOnly;
    private $isAbstract;
    private $skipSql;

    /**
     * The default accessor visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $defaultAccessorVisibility;

    /**
     * The default mutator visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $defaultMutatorVisibility;

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
        parent::__construct();

        $this->name = $name;

        $this->fields = new Set();
        $this->fieldsByName = new Map();
        $this->fieldsByLowercaseName = new Map();
        $this->relations = new Set();
        $this->foreignEntityNames = new Set();
        $this->indices = new Set();
        $this->referrers = new Set();
        $this->unices = new Set();
        $this->initBehaviors();

        $this->idMethod = IdMethod::NO_ID_METHOD;
        $this->idMethodParameters = new Set();

        $this->allowPkInsert = false;
        $this->isAbstract = false;
        $this->isCrossRef = false;
        $this->readOnly = false;
        $this->reloadOnInsert = false;
        $this->reloadOnUpdate = false;
        $this->skipSql = false;
        $this->defaultAccessorVisibility = static::VISIBILITY_PUBLIC;
        $this->defaultMutatorVisibility = static::VISIBILITY_PUBLIC;
    }

    public function __clone()
    {
        $fields = [];
        if ($this->fields) {
            foreach ($this->fields as $oldCol) {
                $col = clone $oldCol;
                $fields[] = $col;
                $this->fieldsByName[$col->getName()] = $col;
                $this->fieldsByLowercaseName[strtolower($col->getName())] = $col;
                //            $this->fieldsByPhpName[$col->getName()] = $col;
            }
            $this->fields = $fields;
        }
    }

    /**
     * Retrieves the configuration object.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        if ($this->database) {
            return $this->database->getGeneratorConfig();
        }

        return null;
    }



    /**
     * @TODO
     */
    public function setupObject()
    {
        parent::setupObject();

        $this->setName($this->getAttribute('name'));
        $this->tableName = $this->getAttribute('tableName');

        if ($this->getAttribute('activeRecord')) {
            $this->activeRecord = 'true' === $this->getAttribute('activeRecord');
        }
        $this->allowPkInsert = $this->boolValue($this->getAttribute('allowPkInsert'));

        $this->skipSql = $this->boolValue($this->getAttribute('skipSql'));
        $this->readOnly = $this->boolValue($this->getAttribute('readOnly'));

        $this->isAbstract = $this->boolValue($this->getAttribute('abstract'));
        $this->baseClass = $this->getAttribute('baseClass');
        $this->alias = $this->getAttribute('alias');
        $this->repository = $this->getAttribute('repository');

        if ('true' === $this->repository) {
            $this->repository = true;
        } else if ('false' === $this->repository) {
            $this->repository = false;
        }

        if ($this->getAttribute('identifierQuoting')) {
            $this->identifierQuoting = $this->boolValue($this->getAttribute('identifierQuoting'));
        }

        $this->description = $this->getAttribute('description');

        $this->reloadOnInsert = $this->boolValue($this->getAttribute('reloadOnInsert'));
        $this->reloadOnUpdate = $this->boolValue($this->getAttribute('reloadOnUpdate'));
        $this->isCrossRef = $this->boolValue($this->getAttribute('isCrossRef', false));
        $this->defaultStringFormat = $this->getAttribute('defaultStringFormat');

        $this->lazySetupObject();
    }

    /**
     * @TODO
     */
    protected function lazySetupObject() {
        if ($this->database) {
            if (null === $this->idMethod) {
                $this->idMethod = $this->getAttribute('idMethod', $this->database->getDefaultIdMethod());
            }

            if (null === $this->heavyIndexing) {
                $this->heavyIndexing = (
                    $this->boolValue($this->getAttribute('heavyIndexing'))
                    || (
                        'false' !== $this->getAttribute('heavyIndexing')
                        && $this->database->isHeavyIndexing()
                    )
                );
            }

            if (null === $this->defaultAccessorVisibility) {
                $this->defaultAccessorVisibility = $this->getAttribute(
                    'defaultAccessorVisibility',
                    $this->database->getAttribute('defaultAccessorVisibility', static::VISIBILITY_PUBLIC)
                );
            }

            if (null === $this->defaultMutatorVisibility) {
                $this->defaultMutatorVisibility = $this->getAttribute(
                    'defaultMutatorVisibility',
                    $this->database->getAttribute('defaultMutatorVisibility', static::VISIBILITY_PUBLIC)
                );
            }
        }
    }

    /**
     * @TODO externalize !
     * @param bool $throwErrors
     */
    public function finalizeDefinition($throwErrors = false)
    {
        $this->setupReferrers($throwErrors);
    }

    /**
     * @TODO externalize ?
     * Browses the foreign keys and creates referrers for the foreign entity.
     * This method can be called several times on the same entity. It only
     * adds the missing referrers and is non-destructive.
     * Warning: only use when all the entitys were created.
     *
     * @param  bool $throwErrors
     *
     * @throws BuildException
     */
    protected function setupReferrers($throwErrors = false)
    {
        foreach ($this->getRelations() as $relation) {
            $this->setupReferrer($relation, $throwErrors);
        }
    }

    /**
     * @TODO externalize ?
     * @param Relation $relation
     * @param bool     $throwErrors
     */
    protected function setupReferrer(Relation $relation, $throwErrors = false)
    {
        $entity = $relation->getEntity();
        // entity referrers
        $hasEntity = $entity->getDatabase()->hasEntity($relation->getForeignEntityName());
        if (!$hasEntity) {
            throw new BuildException(
                sprintf(
                    'Entity "%s" contains a relation to nonexistent entity "%s". [%s]',
                    $entity->getName(),
                    $relation->getForeignEntityName(),
                    $entity->getDatabase()->getEntityNames()
                )
            );
        }

        $foreignEntity = $entity->getDatabase()->getEntity($relation->getForeignEntityName());
        $referrers = $foreignEntity->getReferrers();
        if (null === $referrers || !in_array($relation, $referrers, true)) {
            $foreignEntity->addReferrer($relation);
        }

        // foreign pk's
        $localFieldNames = $relation->getLocalFields();
        foreach ($localFieldNames as $localFieldName) {
            $localField = $entity->getField($localFieldName);
            if (null !== $localField) {
                if ($localField->isPrimaryKey() && !$entity->getContainsForeignPK()) {
                    $entity->setContainsForeignPK(true);
                }
            } elseif ($throwErrors) {
                // give notice of a schema inconsistency.
                // note we do not prevent the npe as there is nothing
                // that we can do, if it is to occur.
                throw new BuildException(
                    sprintf(
                        'Entity "%s" contains a foreign key with nonexistent local field "%s"',
                        $entity->getName(),
                        $localFieldName
                    )
                );
            }
        }

        // foreign field references
        $foreignFields = $relation->getForeignFieldObjects();
        foreach ($foreignFields as $foreignField) {
            if (null === $foreignEntity) {
                continue;
            }
            if (null !== $foreignField) {
                if (!$foreignField->hasReferrer($relation)) {
                    $foreignField->addReferrer($relation);
                }
            } elseif ($throwErrors) {
                // if the foreign field does not exist, we may have an
                // external reference or a misspelling
                throw new BuildException(
                    sprintf(
                        'Entity "%s" contains a foreign key to entity "%s" with nonexistent field "%s"',
                        $entity->getName(),
                        $foreignEntity->getName(),
                        $foreignField->getName()
                    )
                );
            }
        }
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
        $tableName = !$this->tableName ? NamingTool::toUnderscore($this->name) : $this->tableName;

        return $tableName;
    }

    /**
     * The table name with database scope.
     *
     * @return string
     */
    public function getScopedTableName(): string
    {
        $tableName = !$this->tableName ? NamingTool::toUnderscore($this->name) : $this->tableName;

        if ($this->getDatabase()) {
            return $this->getDatabase()->getScope() . $this->tableName;
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

    /**
     * Returns the class name with namespace.
     *
     * @return string
     */
    public function getFullName(): string
    {
        $name = $this->getName();
        $namespace = $this->getNamespace();

        if (!$namespace && $this->getDatabase()) {
            $namespace = $this->getDatabase()->getNamespace();
        }

        if ($namespace) {
            return $namespace . '\\' . $name;
        } else {
            return $name;
        }
    }

    /**
     * @TODO convenient method. remove?
     *
     * Returns the camelCase version of PHP name.
     *
     * The studly name is the PHP name with the first character lowercase.
     *
     * @return string
     */
    public function getCamelCaseName(): string
    {
        return lcfirst($this->getName());
    }

    /**
     * Sets the entity description.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): Entity
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Returns the entity description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns whether or not the entity has a description.
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return !empty($this->description);
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
        if (!$this->database !== null && $this->database !== $database) {
            $this->database->removeEntity($this);
        }
        $this->database = $database;
        $this->database->addEntity($this);

        return $this;
    }

    /**
     * Get the database that contains this entity.
     *
     * @return Database
     */
    public function getDatabase(): ?Database
    {
        return $this->database;
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
     * @return Field
     */
    public function getChildrenField(): Field
    {
        return $this->inheritanceField;
    }

    /**
     * Returns the subclasses that can be created from this entity.
     *
     * @return array
     */
    public function getChildrenNames(): array
    {
        if (null === $this->inheritanceField || !$this->inheritanceField->isEnumeratedClasses()) {
            return null;
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
        if ($this->fieldsByName->has($field->getName())) {
            throw new EngineException(sprintf('Field "%s" declared twice in entity "%s"', $col->getName(), $this->getName()));
        }

        $this->fields->add($field);
        $this->fieldsByName->set($field->getName(), $field);
        $this->fieldsByLowercaseName->set(strtolower($field->getName()), $field);

        $field->setPosition($this->fields->size());
        $field->setEntity($this);

        if ($field->requiresTransactionInPostgres()) {
            $this->needsTransactionInPostgres = true;
        }

        if ($field->isInheritance()) {
            $this->inheritanceField = $field;
        }

        return $this;
    }

    /**
     * Adds several fields at once.
     *
     * @param Field[] $fields An array of Field instance
     * @return $this
     */
    public function addFields(array $fields): Entity
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }

    /**
     * Returns whether or not the entity has a field.
     *
     * @param Field|string $field The Field object or its name
     * @param bool $caseInsensitive Whether the check is case insensitive.
     *
     * @return bool
     */
    public function hasField($field, bool $caseInsensitive = false): bool
    {
        if ($field instanceof Field) {
            return $this->fields->contains($field);
        }

        if ($caseInsensitive) {
            return $this->fieldsByLowercaseName->has(strtolower($field));
        }

        return $this->fieldsByName->has($field);
    }

    /**
     * Returns the Field object with the specified name.
     *
     * @param string $name The name of the field (e.g. 'my_field')
     * @param bool $caseInsensitive Whether the check is case insensitive.
     *
     * @return Field
     */
    public function getField(string $name, bool $caseInsensitive = false): Field
    {
        if (!$this->hasField($name, $caseInsensitive)) {
            throw new \InvalidArgumentException(sprintf('Field `%s` not found in Entity `%s` [%s]', $name, $this->getName(), implode(',', array_keys($this->fieldsByName))));
        }

        if ($caseInsensitive) {
            return $this->fieldsByLowercaseName->get(strtolower($name));
        }

        return $this->fieldsByName->get($name);
    }

    /**
     * Returns an array containing all Field objects in the entity.
     *
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @TODO This method has no relevance to the model. Could just be static - what to do with it?
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

    private function getFieldPosition(Field $field): int
    {
        return $this->fields->indexOf($field);
    }

    /**
     * @TODO: This shouldn't be a method. period. Should automatically managed by add/remove Field
     */
    public function adjustFieldPositions()
    {
        $nbFields = $this->fields->size();
        for ($i = 0; $i < $nbFields; $i++) {
            $this->fields[$i]->setPosition($i + 1);
        }
    }

    /**
     * Removes a field from the entity.
     *
     * @param  Field|string $field The Field or its name
     *
     * @throws EngineException
     * @return $this
     */
    public function removeField($field): Entity
    {
        if (is_string($field)) {
            $field = $this->getField($field);
        }

        if (null === $field || !$this->fields->contains($field)) {
            throw new EngineException(sprintf('No field named %s found in entity %s.', $field->getName(), $this->getName()));
        }

        $this->fields->remove($field);
        $this->fieldsByName->remove($field->getName());
        $this->fieldsByLowercaseName->remove(strtolower($field->getName()));

        $this->adjustFieldPositions();
        // @FIXME: also remove indexes and validators on this field?

        return $this;
    }


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
            return in_array($fieldName, $relation->getLocalFields());
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
     * Returns the list of entitys referenced by foreign keys in this entity.
     *
     * @return array
     */
    public function getForeignEntityNames()
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
    public function getReferrers()
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

        if (!$index->getFields()) {
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
    public function hasIndex($name)
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
            if (count($keys) === $index->fields->size()) {
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
            $index = $this->indices->find($index, function(Index $index, $query) {
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
     * Sets the method strategy for generating primary keys.
     *
     * @param string $idMethod
     * @return $this
     */
    public function setIdMethod(string $idMethod): Entity
    {
        $this->idMethod = $idMethod;
        return $this;
    }

    /**
     * Returns the method strategy for generating primary keys.
     *
     * [HL] changing behavior so that Database default method is returned
     * if no method has been specified for the entity.
     *
     * @return string
     */
    public function getIdMethod(): string
    {
        return $this->idMethod;
    }

    /**
     * Adds a new parameter for the strategy that generates primary keys.
     *
     * @param IdMethodParameter $idMethodParameter
     * @return $this
     */
    public function addIdMethodParameter(IdMethodParameter $idMethodParameter): Entity
    {
        $idMethodParameter->setEntity($this);
        $this->idMethodParameters->add($idMethodParameter);

        return $this;
    }

    /**
     * Returns a Collection of parameters relevant for the chosen
     * id generation method.
     *
     * @return IdMethodParameter[]
     */
    public function getIdMethodParameters(): array
    {
        return $this->idMethodParameters->toArray();
    }

    /**
     * Removes a parameter for the strategy that generates primary keys.
     *
     * @param IdMethodParameter $idMethodParameter
     * @return $this
     */
    public function removeIdMethodParameter(IdMethodParameter $idMethodParameter): Entity
    {
        $idMethodParameter->setEntity(null);
        $this->idMethodParameters->remove($idMethodParameter);

        return $this;
    }

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
    public function requiresTransactionInPostgres()
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * @return bool
     */
    public function isHeavyIndexing(): bool
    {
        return $this->heavyIndexing;
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
    public function getIdentifierQuoting()
    {
        return $this->identifierQuoting;
    }

    /**
     * Makes this database reload on insert statement.
     *
     * @param bool $flag True by default
     * @return $this
     */
    public function setReloadOnInsert(bool $flag = true)
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

    /**
     * Sets the default string format for ActiveRecord objects in this entity.
     *
     * Any of 'XML', 'YAML', 'JSON', or 'CSV'.
     *
     * @param  string $format
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public function setDefaultStringFormat(string $format): Entity
    {
        $formats = Database::getSupportedStringFormats();
        $format = strtoupper($format);

        if (!in_array($format, $formats)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Given "%s" default string format is not supported. Only "%s" are valid string formats.',
                    $format,
                    implode(', ', $formats)
                )
            );
        }

        $this->defaultStringFormat = $format;
        return $this;
    }

    /**
     * Returns the default string format for ActiveRecord objects in this entity,
     * or the one for the whole database if not set.
     *
     * @return string
     */
    public function getDefaultStringFormat(): string
    {
        if (null !== $this->defaultStringFormat) {
            return $this->defaultStringFormat;
        }

        return $this->database->getDefaultStringFormat();
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
    }

    /**
     * Sets whether or not this entity contains a foreign primary key.
     *
     * @param $containsForeignPK
     *
     * @return bool
     */
    public function setContainsForeignPK($containsForeignPK)
    {
        $this->containsForeignPK = (bool)$containsForeignPK;
    }

    /**
     * Returns whether or not this entity contains a foreign primary key.
     *
     * @return bool
     */
    public function getContainsForeignPK()
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
        if (IdMethod::NO_ID_METHOD !== $this->getIdMethod()) {
            foreach ($this->getPrimaryKey() as $pk) {
                if ($pk->isAutoIncrement()) {
                    return $pk;
                }
            }
        }
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
     * Sets the default accessor visibility.
     *
     * @param string $defaultAccessorVisibility
     * @return $this
     */
    public function setDefaultAccessorVisibility(string $defaultAccessorVisibility): Entity
    {
        $this->defaultAccessorVisibility = $defaultAccessorVisibility;
        return $this;
    }

    /**
     * Returns the default accessor visibility.
     *
     * @return string
     */
    public function getDefaultAccessorVisibility(): string
    {
        return $this->defaultAccessorVisibility;
    }

    /**
     * Sets the default mutator visibility.
     *
     * @param string $defaultMutatorVisibility
     * @return $this
     */
    public function setDefaultMutatorVisibility(string $defaultMutatorVisibility): Entity
    {
        $this->defaultMutatorVisibility = $defaultMutatorVisibility;
        return $this;
    }

    /**
     * Returns the default mutator visibility.
     *
     * @return string
     */
    public function getDefaultMutatorVisibility(): string
    {
        return $this->defaultMutatorVisibility;
    }

    /**
     * @param bool $activeRecord
     * @return $this
     */
    public function setActiveRecord(bool $activeRecord): Entity
    {
        $this->activeRecord = $activeRecord;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActiveRecord(): bool
    {
        if (null === $this->activeRecord) {
            return $this->getDatabase()->isActiveRecord();
        }

        return $this->activeRecord;
    }

    /**
     * @return bool|null
     */
    public function getActiveRecord()
    {
        return $this->activeRecord;
    }

    /**
     * Returns whether or not the entity behaviors offer additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders()
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
    public function getAdditionalBuilders()
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
    public function getEarlyBehaviors()
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
    public function guessSchemaName()
    {
        if (!$this->schema && $this->database) {
            return $this->database->getSchema();
        }

        return $this->schema;
    }

    /**
     * Returns whether or not this entity is linked to a schema.
     *
     * @return bool
     */
    public function hasSchema()
    {
        return $this->database
        && ($this->schema ?: $this->database->getSchema())
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

    /**
     * @TODO not model related. remove? move to possible parent as helper?
     *
     * Returns a build property value for the database this entity belongs to.
     *
     * @param  string $key
     * @return string
     */
    public function getBuildProperty(string $key): string
    {
        return $this->database ? $this->database->getBuildProperty($key) : '';
    }
}
