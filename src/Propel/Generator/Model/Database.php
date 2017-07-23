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

use Propel\Generator\Model\Parts\ActiveRecordPart;
use Propel\Generator\Model\Parts\BehaviorPart;
use Propel\Generator\Model\Parts\GeneratorPart;
use Propel\Generator\Model\Parts\NamespacePart;
use Propel\Generator\Model\Parts\PlatformMutatorPart;
use Propel\Generator\Model\Parts\SchemaNamePart;
use Propel\Generator\Model\Parts\ScopePart;
use Propel\Generator\Model\Parts\SqlPart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;
use Propel\Generator\Platform\PlatformInterface;
use phootwork\collection\ArrayList;
use phootwork\collection\Map;
use phootwork\collection\Set;
use Propel\Generator\Model\Parts\SchemaPart;

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
    use PlatformMutatorPart;
    use SqlPart;
    use ScopePart;
    use ActiveRecordPart;
    use NamespacePart;
    use GeneratorPart;
    use BehaviorPart;
    use SchemaNamePart;
    use SchemaPart;
    use VendorPart;

    /** @var Map */
    private $domains;

    /** @var Set */
    private $entities;

//     /** @var Map */
//     private $entitiesByName;
//     private $entitiesByLowercaseName;
//     private $entitiesByFullName;
//     private $entitiesByTableName;

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
//         $this->entitiesByName = new Map();
//         $this->entitiesByTableName = new Map();
//         $this->entitiesByLowercaseName = new Map();
//         $this->entitiesByFullName = new Map();
        $this->initBehaviors();
        $this->initSql();

        // default values
        $this->activeRecord = false;
        $this->heavyIndexing = false;
        $this->identifierQuoting = false;
    }

    /**
     * @TODO
     */
    public function __clone()
    {
//         $entities = [];
//         foreach ($this->entities as $oldEntity) {
//             $entity = clone $oldEntity;
//             $entities[] = $entity;
//             $this->entitiesByName[$entity->getName()] = $entity;
//             $this->entitiesByLowercaseName[strtolower($entity->getName())] = $entity;
//             //            $this->entitiesByPhpName[$entity->getName()] = $entity;
//         }
//         $this->entities = $entities;
    }

    protected function getSuperordinate()
    {
        return $this->schema;
    }

//     protected function setupObject()
//     {
//         parent::setupObject();

//         $this->name = $this->getAttribute('name');
//         $this->platformClass = $this->getAttribute('platform');
//         $this->baseClass = $this->getAttribute('baseClass');
//         $this->defaultIdMethod = $this->getAttribute('defaultIdMethod', IdMethod::NATIVE);
//         $this->heavyIndexing = $this->booleanValue($this->getAttribute('heavyIndexing'));
//         $this->identifierQuoting = $this->getAttribute('identifierQuoting') ? $this->booleanValue($this->getAttribute('identifierQuoting')) : false;
//         $this->scope = $this->getAttribute('tablePrefix', $this->getBuildProperty('generator.tablePrefix'));
//         $this->defaultStringFormat = $this->getAttribute('defaultStringFormat', static::DEFAULT_STRING_FORMAT);

//         if ($this->getAttribute('activeRecord')) {
//             $this->activeRecord = 'true' === $this->getAttribute('activeRecord');
//         }
//     }

//     /**
//      * Returns the PlatformInterface implementation for this database.
//      *
//      * @return PlatformInterface
//      */
//     public function getPlatform()
//     {
//         if (null === $this->platform) {
//             if ($this->getParentSchema() && $this->getParentSchema()->getPlatform()) {
//                 return $this->getParentSchema()->getPlatform();
//             }

//             if ($this->getGeneratorConfig()) {
//                 if ($this->platformClass) {
//                     $this->platform = $this->getGeneratorConfig()->createPlatform($this->platformClass);
//                 } else {
//                     $this->platform = $this->getGeneratorConfig()->createPlatformForDatabase($this->getName());
//                 }
//             }
//         }

//         return $this->platform;
//     }

//     /**
//      * Sets the PlatformInterface implementation for this database.
//      *
//      * @param PlatformInterface $platform A Platform implementation
//      * @return $this
//      */
//     public function setPlatform(?PlatformInterface $platform = null): Database
//     {
//         $this->platform = $platform;
//         return $this;
//     }

//     /**
//      * @return boolean
//      */
//     public function isActiveRecord(): bool
//     {
//         return $this->activeRecord;
//     }

//     /**
//      * @param boolean $activeRecord
//      * @return $this
//      */
//     public function setActiveRecord(bool $activeRecord): Database
//     {
//         $this->activeRecord = $activeRecord;
//         return $this;
//     }

    /**
     * @TODO unsave convenient method. Consider removing
     *
     * Returns the max column name's length.
     *
     * @return int
     */
    public function getMaxFieldNameLength(): int
    {
        return $this->getPlatform()->getMaxFieldNameLength();
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

    public function getEntitySize()
    {
        return $this->entities->size();
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
     * @param Entity $entity
     * @return bool
     */
    public function hasEntity(Entity $entity): bool
    {
        return $this->entities->has($entity);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasEntityByName($name): bool
    {
        return $this->entities->search($name, function(Entity $entity, $query) {
            return $entity->getName() === $query;
        });
    }

    /**
     * @param string $name
     *
     * @return Entity
     */
    public function getEntityByName($name): ?Entity
    {
        return $this->entities->find($name, function(Entity $entity, $query) {
            return $entity->getName() === $query;
        });
    }

    /**
     * @param string $fullName
     *
     * @return bool
     */
    public function hasEntityByFullName($fullName): bool
    {
        return $this->entities->search($fullName, function(Entity $entity, $query) {
           return $entity->getFullName() === $query;
        });
    }

    /**
     * @param string $fullName
     *
     * @return Entity
     */
    public function getEntityByFullName($fullName): ?Entity
    {
        return $this->entities->find($fullName, function(Entity $entity, $query) {
            return $entity->getFullName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function hasEntityByTableName($tableName): bool
    {
        return $this->entities->find($tableName, function(Entity $entity, $query) {
            return $entity->getTableName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function getEntityByTableName($tableName): ?Entity
    {
        return $this->entities->find($tableName, function(Entity $entity, $query) {
            return $entity->getTableName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function hasEntityByFullTableName($tableName): bool
    {
        return $this->entities->find($tableName, function(Entity $entity, $query) {
            return $entity->getFullTableName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function getEntityByFullTableName($tableName): ?Entity
    {
        return $this->entities->find($tableName, function(Entity $entity, $query) {
            return $entity->getFullTableName() === $query;
        });
    }

//     /**
//      * Returns the entity with the specified name.
//      *
//      * @param  string  $name
//      * @param  boolean $caseInsensitive
//      * @return Entity
//      */
//     public function getEntity($name, $caseInsensitive = false)
//     {
//         if ($this->hasEntityByFullClassName($name)) {
//             return $this->getEntityByFullClassName($name);
//         }


//         if (!$this->hasEntity($name, $caseInsensitive)) {
//             throw new InvalidArgumentException(
//                 sprintf(
//                     'Entity %s in database %s not found [%s]',
//                     $name,
//                     $this->getName(),
//                     $this->getEntityNames()
//                 )
//             );
//         }

//         if ($caseInsensitive) {
//             return $this->entitiesByLowercaseName[strtolower($name)];
//         }

//         return $this->entitiesByName[$name];
//     }

    /**
     * @TODO is this needed? -> array_map($db->getEntities(), fn {....});
     * @return string[]
     */
    public function getEntityNames(): array
    {
        return $this->entities->map(function (Entity $entity) {
            return $entity->getName();
        })->toArray();
    }

    /**
     * Adds a new entity to this database.
     *
     * @param Entity $entity
     * @return $this
     */
    public function addEntity(Entity $entity): Database
    {
        if (!$this->entities->contains($entity)) {
            $this->entities->add($entity);
            $entity->setDatabase($this);
        }

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
     * @param  string $sequence
     * @return bool
     */
    public function hasSequence(string $sequence): bool
    {
        return $this->sequences->contains($sequence);
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
     * @TODO unsave convenient method. Consider removing
     *
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
    protected function registerSchema(Schema $schema)
    {
        $schema->addDatabase($this);
    }

    protected function unregisterSchema(Schema $schema)
    {
        $schema->removeDatabase($this);
    }

    /**
     * Adds a domain object to this database.
     *
     * @param Domain $domain
     * @return $this
     */
    public function addDomain(Domain $domain): Database
    {
        if (!$this->domains->contains($domain)) {
            $domain->setDatabase($this);
            $this->domains->set($domain->getName(), $domain);
        }
        return $this;
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
     * @TODO This is wrong here, about to kill it off here
     *
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
     * @TODO Externalize
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




}
