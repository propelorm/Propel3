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

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Parts\ActiveRecordPart;
use Propel\Generator\Model\Parts\BehaviorPart;
use Propel\Generator\Model\Parts\GeneratorPart;
use Propel\Generator\Model\Parts\NamespacePart;
use Propel\Generator\Model\Parts\SchemaNamePart;
use Propel\Generator\Model\Parts\ScopePart;
use Propel\Generator\Model\Parts\SqlPart;
use Propel\Generator\Model\Parts\VendorPart;
use Propel\Generator\Platform\PlatformInterface;
use phootwork\collection\ArrayList;
use phootwork\collection\Map;
use phootwork\collection\Set;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\GeneratorConfigPart;

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally<jmcnally@collab.net> (Torque)
 * @author Martin Poeschl<mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall<dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Database
{
    use SuperordinatePart;
    use GeneratorConfigPart;
    use SqlPart;
    use ScopePart;
    use ActiveRecordPart;
    use NamespacePart;
    use GeneratorPart;
    use BehaviorPart;
    use SchemaNamePart;
    use VendorPart;

    /**
     * The database's platform.
     *
     * @var PlatformInterface
     */
    private $platform;


    /** @var Map */
    private $domains;


    /** @var Schema */
    private $schema;

    /** @var Set */
    private $entities;

    /** @var Map */
    private $entitiesByName;
    private $entitiesByLowercaseName;
    private $entitiesByFullName;
    private $entitiesByTableName;

    /**
     * @var ArrayList
     */
    private $sequences;


    /**
     * Constructs a new Database object.
     *
     * @param string $name The database's name
     * @param PlatformInterface $platform The database's platform
     */
    public function __construct(?string $name = null, ?PlatformInterface $platform = null)
    {
        parent::__construct();

        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        // init
        $this->sequences = new ArrayList();
        $this->domains = new Map();
        $this->entities = new Set();
        $this->entitiesByName = new Map();
        $this->entitiesByLowercaseName = new Map();
        $this->entitiesByFullName = new Map();
        $this->initBehaviors();
        $this->initSql();

        // default values
        $this->activeRecord = false;
        $this->heavyIndexing = false;
        $this->identifierQuoting = false;
    }

    protected function getSuperordinate()
    {
        return $this->schema;
    }

    protected function setupObject()
    {
        parent::setupObject();

        $this->name = $this->getAttribute('name');
        $this->platformClass = $this->getAttribute('platform');
        $this->baseClass = $this->getAttribute('baseClass');
        $this->defaultIdMethod = $this->getAttribute('defaultIdMethod', IdMethod::NATIVE);
        $this->heavyIndexing = $this->booleanValue($this->getAttribute('heavyIndexing'));
        $this->identifierQuoting = $this->getAttribute('identifierQuoting') ? $this->booleanValue($this->getAttribute('identifierQuoting')) : false;
        $this->scope = $this->getAttribute('tablePrefix', $this->getBuildProperty('generator.tablePrefix'));
        $this->defaultStringFormat = $this->getAttribute('defaultStringFormat', static::DEFAULT_STRING_FORMAT);

        if ($this->getAttribute('activeRecord')) {
            $this->activeRecord = 'true' === $this->getAttribute('activeRecord');
        }
    }

    /**
     * Returns the PlatformInterface implementation for this database.
     *
     * @return PlatformInterface
     */
    public function getPlatform()
    {
        if (null === $this->platform) {
            if ($this->getParentSchema() && $this->getParentSchema()->getPlatform()) {
                return $this->getParentSchema()->getPlatform();
            }

            if ($this->getGeneratorConfig()) {
                if ($this->platformClass) {
                    $this->platform = $this->getGeneratorConfig()->createPlatform($this->platformClass);
                } else {
                    $this->platform = $this->getGeneratorConfig()->createPlatformForDatabase($this->getName());
                }
            }
        }

        return $this->platform;
    }

    /**
     * Sets the PlatformInterface implementation for this database.
     *
     * @param PlatformInterface $platform A Platform implementation
     * @return $this
     */
    public function setPlatform(?PlatformInterface $platform = null): Database
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isActiveRecord(): bool
    {
        return $this->activeRecord;
    }

    /**
     * @param boolean $activeRecord
     * @return $this
     */
    public function setActiveRecord(bool $activeRecord): Database
    {
        $this->activeRecord = $activeRecord;
        return $this;
    }

    /**
     * Returns the max column name's length.
     *
     * @return int
     */
    public function getMaxFieldNameLength(): int
    {
        return $this->getPlatform()->getMaxFieldNameLength();
    }

    /**
     * Returns the database name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the database name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): Database
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the name of the default ID method strategy.
     * This parameter can be overridden at the entity level.
     *
     * @return string
     */
    public function getDefaultIdMethod(): string
    {
        return $this->defaultIdMethod;
    }

    /**
     * Sets the name of the default ID method strategy.
     * This parameter can be overridden at the entity level.
     *
     * @param string $strategy
     * @return $this
     */
    public function setDefaultIdMethod($strategy): Database
    {
        $this->defaultIdMethod = $strategy;
        return $this;
    }

    /**
     * Returns the list of supported string formats
     *
     * @return array
     */
    public static function getSupportedStringFormats(): array
    {
        return ['XML', 'YAML', 'JSON', 'CSV'];
    }

    /**
     * Sets the default string format for ActiveRecord objects in this entity.
     * This parameter can be overridden at the entity level.
     *
     * Any of 'XML', 'YAML', 'JSON', or 'CSV'.
     *
     * @param string $format
     * @throws InvalidArgumentException
     * @return $this
     */
    public function setDefaultStringFormat(string $format): Database
    {
        $formats = static::getSupportedStringFormats();

        $format = strtoupper($format);
        if (!in_array($format, $formats)) {
            throw new InvalidArgumentException(sprintf('Given "%s" default string format is not supported. Only "%s" are valid string formats.', $format, implode(', ', $formats)));
        }

        $this->defaultStringFormat = $format;
        return $this;
    }

    /**
     * Returns the default string format for ActiveRecord objects in this entity.
     * This parameter can be overridden at the entity level.
     *
     * @return string
     */
    public function getDefaultStringFormat(): string
    {
        return $this->defaultStringFormat;
    }

    /**
     * Returns whether or not heavy indexing is enabled.
     *
     * This is an alias for getHeavyIndexing().
     *
     * @return boolean
     */
    public function isHeavyIndexing(): bool
    {
        return $this->getHeavyIndexing();
    }

    /**
     * Returns whether or not heavy indexing is enabled.
     *
     * This is an alias for isHeavyIndexing().
     *
     * @return boolean
     */
    public function getHeavyIndexing(): bool
    {
        return $this->heavyIndexing;
    }

    /**
     * Sets whether or not heavy indexing is enabled.
     *
     * @param boolean $flag
     * @return $this
     */
    public function setHeavyIndexing(bool $flag = true): Database
    {
        $this->heavyIndexing = $flag;
        return $this;
    }

    /**
     * Return the list of all entities.
     *
     * @return Entity[]
     */
    public function getEntities(): array
    {
        return $this->entities->toArray();
    }

    /**
     * Return the number of entities in the database.
     *
     * Read-only entities are excluded from the count.
     *
     * @return integer
     */
    public function countEntities(): int
    {
        $count = 0;
        foreach ($this->entities as $entity) {
            if (!$entity->isReadOnly()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns the list of all entities that have a SQL representation.
     *
     * @return Entity[]
     */
    public function getEntitiesForSql(): array
    {
        return $this->entities->filter(function(Entity $entity) {
            return !$entity->isSkipSql();
        })->toArray();
    }

    /**
     * Returns whether or not the database has a entity.
     *
     * @param string|Entity $entity
     * @param bool $caseInsensitive
     * @return bool
     */
    public function hasEntity($entity, bool $caseInsensitive = false): bool
    {
        if ($entity instanceof Entity) {
            return $this->entities->has($entity);
        }

        if ($this->hasEntityByFullClassName($name)) {
            return true;
        }

        if ($caseInsensitive) {
            return $this->entitiesByLowercaseName->has(strtolower($name));
        }

        return $this->entitiesByName->has($name);
    }

    /**
     * @param string $fullClassName
     *
     * @return bool
     */
    public function hasEntityByFullClassName($fullClassName): bool
    {
        return $this->entitiesByFullName->has($fullClassName);
    }

    /**
     * @param string $fullClassName
     *
     * @return Entity
     */
    public function getEntityByFullClassName($fullClassName): ?Entity
    {
        return $this->entitiesByFullName->get($fullClassName);
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function getEntityByTableName($tableName)
    {
        $schema = $this->getSchemaName() ?: $this->getName();

        if (!isset($this->entitiesByTableName[$tableName])) {
            if (isset($this->entitiesByTableName[$schema . $this->getSchemaDelimiter() . $tableName])) {
                return $this->entitiesByTableName[$schema . $this->getSchemaDelimiter() . $tableName];
            }

            throw new \InvalidArgumentException("Entity by table name $tableName not found in {$this->getName()}.");
        }

        return $this->entitiesByTableName[$tableName];
    }

    /**
     * Returns the entity with the specified name.
     *
     * @param  string  $name
     * @param  boolean $caseInsensitive
     * @return Entity
     */
    public function getEntity($name, $caseInsensitive = false)
    {
        if ($this->hasEntityByFullClassName($name)) {
            return $this->getEntityByFullClassName($name);
        }


        if (!$this->hasEntity($name, $caseInsensitive)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Entity %s in database %s not found [%s]',
                    $name,
                    $this->getName(),
                    $this->getEntityNames()
                )
            );
        }

        if ($caseInsensitive) {
            return $this->entitiesByLowercaseName[strtolower($name)];
        }

        return $this->entitiesByName[$name];
    }

    /**
     * @return string
     */
    public function getEntityNames()
    {
        return implode(',', $this->entitiesByName->keys()->toArray());
//         return $this->entitiesByName->keys()->toArray()
    }

//    /**
//     * Returns whether or not the database has a entity identified by its
//     * PHP name.
//     *
//     * @param  string  $phpName
//     * @return boolean
//     */
//    public function hasEntityByPhpName($phpName)
//    {
//        return isset($this->entitiesByPhpName[$phpName]);
//    }
//
//    /**
//     * Returns the entity object with the specified PHP name.
//     *
//     * @param  string $phpName
//     * @return Entity
//     */
//    public function getEntityByPhpName($phpName)
//    {
//        if (isset($this->entitiesByPhpName[$phpName])) {
//            return $this->entitiesByPhpName[$phpName];
//        }
//
//        return null; // just to be explicit
//    }

    /**
     * Adds a new entity to this database.
     *
     * @param Entity $entity
     * @return $this
     */
    public function addEntity(Entity $entity): Database
    {
        if ($this->entitiesByFullName->has($entity->getFullName())) {
            throw new EngineException(sprintf('Entity "%s" declared twice', $entity->getName()));
        }

        $this->entities->add($entity);
        $this->entitiesByFullName->set($entity->getFullName(), $entity);
        $this->entitiesByTableName->set($entity->getFullTableName(), $entity);
        $this->entitiesByName->set($entity->getName(), $entity);
        $this->entitiesByLowercaseName->set(strtolower($entity->getName()), $entity);
//        $this->entitiesByPhpName[$entity->getName()] = $entity;

//        $this->computeEntityNamespace($entity);

        $entity->setDatabase($this);

        return $this;
    }

    /**
     * Adds several entities at once.
     *
     * @param Entity[] $entities An array of Entity instances
     * @return $this
     */
    public function addEntities(array $entities): Database
    {
        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }
        return $this;
    }

    /**
     * @param string[] $sequences
     * @return $this
     */
    public function setSequences(array $sequences): Database
    {
        $this->sequences->clear();
        $this->sequences->addAll($sequences);
        return $this;
    }

    /**
     * @return string[]
     */
    public function getSequences(): array
    {
        return $this->sequences->toArray();
    }

    /**
     * @param string $sequence
     * @return $this
     */
    public function addSequence(string $sequence): Database
    {
        $this->sequences->add($sequence);
        return $this;
    }

    /**
     * @param string $sequence
     * @return $this
     */
    public function removeSequence(string $sequence): Database
    {
        $this->sequences->remove($sequence);
        return $this;
    }

    /**
     * @param  string $sequence
     * @return bool
     */
    public function hasSequence(string $sequence): bool
    {
        return $this->sequences->contains($sequence);
    }

    /**
     * Returns the schema delimiter character.
     *
     * For example, the dot character with mysql when
     * naming entities. For instance: schema.the_entity.
     *
     * @return string
     */
    public function getSchemaDelimiter(): string
    {
        return $this->getPlatform()->getSchemaDelimiter();
    }

//    /**
//     * Sets the database's schema.
//     *
//     * @param string $schema
//     */
//    public function setSchema($schema)
//    {
//        $oldSchema = $this->schemaName;
//        if ($this->schemaName !== $schema && $this->getPlatform()) {
//            $schemaDelimiter = $this->getPlatform()->getSchemaDelimiter();
//            $fixHash = function (&$array) use ($schema, $oldSchema, $schemaDelimiter) {
//                foreach ($array as $k => $v) {
//                    if ($schema && $this->getPlatform()->supportsSchemas()) {
//                        if (false === strpos($k, $schemaDelimiter)) {
//                            $array[$schema . $schemaDelimiter . $k] = $v;
//                            unset($array[$k]);
//                        }
//                    } elseif ($oldSchema) {
//                        if (false !== strpos($k, $schemaDelimiter)) {
//                            $array[explode($schemaDelimiter, $k)[1]] = $v;
//                            unset($array[$k]);
//                        }
//                    }
//                }
//            };
//
//            $fixHash($this->entitiesByName);
//            $fixHash($this->entitiesByLowercaseName);
//        }
//        parent::setSchema($schema);
//    }

//    /**
//     * Computes the entity namespace based on the current relative or
//     * absolute entity namespace and the database namespace.
//     *
//     * @param  Entity  $entity
//     * @return string
//     */
//    private function computeEntityNamespace(Entity $entity)
//    {
//        $namespace = $entity->getNamespace();
//        if ($this->isAbsoluteNamespace($namespace)) {
//            $namespace = ltrim($namespace, '\\');
//            $entity->setNamespace($namespace);
//
//            return $namespace;
//        }
//
//        if ($namespace = $this->getNamespace()) {
//            if ($entity->getNamespace()) {
//                $namespace .= '\\'.$entity->getNamespace();
//            }
//
//            $entity->setNamespace($namespace);
//        }
//
//        return $namespace;
//    }

    /**
     * Sets the parent schema
     *
     * @param Schema $parent The parent schema
     * @return $this
     */
    public function setSchema(Schema $parent): Database
    {
        $this->schema = $parent;
        return $this;
    }

    /**
     * Returns the parent schema
     *
     * @return Schema
     */
    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    /**
     * Adds a domain object to this database.
     *
     * @param Domain $domain
     * @return $this
     */
    public function addDomain(Domain $domain): Database
    {
        $domain->setDatabase($this);
        $this->domains->set($domain->getName(), $domain);
        return $this;
//         if ($data instanceof Domain) {
//             $domain = $data; // alias
//             $domain->setDatabase($this);
//             $this->domainMap[$domain->getName()] = $domain;

//             return $domain;
//         }

//         $domain = new Domain();
//         $domain->setDatabase($this);
//         $domain->loadMapping($data);

//         return $this->addDomain($domain); // call self w/ different param
    }

    /**
     * Returns the already configured domain object by its name.
     *
     * @param string $name
     * @return Domain
     */
    public function getDomain(string $name): ?Domain
    {
        return $this->domains->get($name);
    }

    /**
     * Returns the GeneratorConfigInterface object.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        if ($this->schema) {
            return $this->schema->getGeneratorConfig();
        }
    }

    /**
     * Returns the configuration property identified by its name.
     *
     * @see \Propel\Common\Config\ConfigurationManager::getConfigProperty() method
     *
     * @param  string $name
     * @return string
     */
    public function getBuildProperty($name)
    {
        if ($config = $this->getGeneratorConfig()) {
            return $config->getConfigProperty($name);
        }
    }

    /**
     * Returns the entity scope for this database.
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Sets the entities' scope.
     *
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope): Database
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Returns the next behavior on all entities, ordered by behavior priority,
     * and skipping the ones that were already executed.
     *
     * @return Behavior
     */
    public function getNextEntityBehavior()
    {
        // order the behaviors according to Behavior::$entityModificationOrder
        $behaviors = [];
        $nextBehavior = null;
        foreach ($this->entities as $entity) {
            foreach ($entity->getBehaviors() as $behavior) {
                if (!$behavior->isEntityModified()) {
                    $behaviors[$behavior->getEntityModificationOrder()][] = $behavior;
                }
            }
        }
        ksort($behaviors);
        if (count($behaviors)) {
            $nextBehavior = $behaviors[key($behaviors)][0];
        }

        return $nextBehavior;
    }

    /**
     * Finalizes the setup process.
     *
     */
    public function doFinalInitialization()
    {
        // execute database behaviors
        foreach ($this->getBehaviors() as $behavior) {
            $behavior->modifyDatabase();
        }

        // execute entity behaviors (may add new entities and new behaviors)
        while ($behavior = $this->getNextEntityBehavior()) {
            $behavior->getEntityModifier()->modifyEntity();
            $behavior->setEntityModified(true);
        }

        if ($this->getPlatform()) {
            $this->getPlatform()->finalizeDefinition($this);
        }
    }

    /**
     * @param Behavior $behavior
     */
    protected function registerBehavior(Behavior $behavior)
    {
        $behavior->setDatabase($this);
    }

    /**
     * @param Behavior $behavior
     */
    protected function unregisterBehavior(Behavior $behavior)
    {
        $behavior->setDatabase(null);
    }

    public function __toString(): string
    {
        return $this->toSql();
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        $entities = [];
        foreach ($this->getEntities() as $entity) {
            $columns = [];
            foreach ($entity->getFields() as $column) {
                $columns[] = sprintf("      %s %s %s %s %s %s",
                    $column->getName(),
                    $column->getType(),
                    $column->getSize() ? '(' . $column->getSize() . ')' : '',
                    $column->isPrimaryKey() ? 'PK' : '',
                    $column->isNotNull() ? 'NOT NULL' : '',
                    $column->getDefaultValueString() ? "'".$column->getDefaultValueString()."'" : '',
                    $column->isAutoIncrement() ? 'AUTO_INCREMENT' : ''
                );
            }

            $fks = [];
            foreach ($entity->getRelations() as $fk) {
                $fks[] = sprintf("      %s to %s.%s (%s => %s)",
                    $fk->getName(),
                    $fk->getForeignSchemaName(),
                    $fk->getForeignEntityCommonName(),
                    join(', ', $fk->getLocalFields()),
                    join(', ', $fk->getForeignFields())
                );
            }

            $indices = [];
            foreach ($entity->getIndices() as $index) {
                $indexFields = [];
                foreach ($index->getFields() as $indexFieldName) {
                    $indexFields[] = sprintf('%s (%s)', $indexFieldName, $index->getFieldSize($indexFieldName));
                }
                $indices[] = sprintf("      %s (%s)",
                    $index->getName(),
                    join(', ', $indexFields)
                );
            }

            $unices = [];
            foreach ($entity->getUnices() as $index) {
                $unices[] = sprintf("      %s (%s)",
                    $index->getName(),
                    join(', ', $index->getFields())
                );
            }

            $entityDef = sprintf("  %s (%s):\n%s",
                $entity->getName(),
                $entity->getCommonName(),
                implode("\n", $columns)
            );

            if ($fks) {
                $entityDef .= "\n    FKs:\n" . implode("\n", $fks);
            }

            if ($indices) {
                $entityDef .= "\n    indices:\n" . implode("\n", $indices);
            }

            if ($unices) {
                $entityDef .= "\n    unices:\n". implode("\n", $unices);
            }

            $entities[] = $entityDef;
        }

        return sprintf("%s:\n%s",
            $this->getName() . ($this->getSchema() ? '.'. $this->getSchema() : ''),
            implode("\n", $entities)
        );
    }

    /**
     * Sets the default accessor visibility.
     *
     * @param string $defaultAccessorVisibility
     * @return $this
     */
    public function setDefaultAccessorVisibility(string $defaultAccessorVisibility): Database
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
    public function setDefaultMutatorVisibility(string $defaultMutatorVisibility): Database
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

    public function __clone()
    {
        $entities = [];
        foreach ($this->entities as $oldEntity) {
            $entity = clone $oldEntity;
            $entities[] = $entity;
            $this->entitiesByName[$entity->getName()] = $entity;
            $this->entitiesByLowercaseName[strtolower($entity->getName())] = $entity;
//            $this->entitiesByPhpName[$entity->getName()] = $entity;
        }
        $this->entities = $entities;
    }

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        return $this->identifierQuoting;
    }

    /**
     * @param boolean $identifierQuoting
     * @return $this
     */
    public function setIdentifierQuoting(bool $identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;
        return $this;
    }

}
