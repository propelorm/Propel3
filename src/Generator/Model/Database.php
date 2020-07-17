<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\ArrayList;
use phootwork\collection\Map;
use phootwork\collection\Set;
use phootwork\lang\Text;
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

    private Map $domains;
    private Set $entities;
    private ArrayList $sequences;

    /**
     * Constructs a new Database object.
     *
     * @param string $name The database's name
     * @param PlatformInterface $platform The database's platform
     */
    public function __construct(string $name = '', PlatformInterface $platform = null)
    {
        $this->setName($name);

        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        // init
        $this->sequences = new ArrayList();
        $this->domains = new Map();
        $this->entities = new Set();
        $this->initBehaviors();
        $this->initSql();
        $this->initVendor();

        // default values
        $this->activeRecord = false;
        $this->identifierQuoting = false;
    }

    /**
     * @return Schema
     */
    protected function getSuperordinate(): ?Schema
    {
        return $this->getSchema();
    }

    /**
     * Return the list of all entities.
     *
     * @return Set
     */
    public function getEntities(): Set
    {
        return $this->entities;
    }

    /**
     * Return the number of entities.
     *
     * @return int
     */
    public function getEntitySize(): int
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
        return $this->entities->findAll(fn(Entity $element) => !$element->isReadOnly())->count();
    }

    /**
     * Returns the list of all entities that have a SQL representation.
     *
     * @return Set
     */
    public function getEntitiesForSql(): Set
    {
        return $this->entities->filter(fn(Entity $entity) => !$entity->isSkipSql());
    }

    /**
     * Returns whether or not the database has a entity.
     *
     * @param Entity $entity
     * @return bool
     */
    public function hasEntity(Entity $entity): bool
    {
        return $this->entities->contains($entity);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasEntityByName(string $name): bool
    {
        return $this->entities->search($name,
            fn(Entity $entity, string $query): bool => $entity->getName()->toString() === $query
        );
    }

    /**
     * @param string $name
     *
     * @return Entity
     */
    public function getEntityByName(string $name): ?Entity
    {
        return $this->entities->find($name,
            fn(Entity $entity, string $query): bool => $entity->getName()->toString() === $query
        );
    }

    /**
     * @param string $fullName
     *
     * @return bool
     */
    public function hasEntityByFullName(string $fullName): bool
    {
        return $this->entities->search($fullName,
            fn(Entity $entity, string $query): bool => $entity->getFullName()->toString() === $query
        );
    }

    /**
     * @param string $fullName
     *
     * @return Entity
     */
    public function getEntityByFullName(string $fullName): ?Entity
    {
        return $this->entities->find($fullName,
            fn(Entity $entity, string $query): bool => $entity->getFullName()->toString() === $query
        );
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return bool
     */
    public function hasEntityByTableName(string $tableName): bool
    {
        return $this->entities->search($tableName,
            fn(Entity $entity, string $query): bool => $entity->getTableName()->toString() === $query
        );
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function getEntityByTableName(string $tableName): ?Entity
    {
        return $this->entities->find($tableName,
            fn(Entity $entity, string $query): bool => $entity->getTableName()->toString() === $query
        );
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return bool
     */
    public function hasEntityByFullTableName(string $tableName): bool
    {
        return $this->entities->search($tableName,
            fn(Entity $entity, string $query): bool => $entity->getFullTableName()->toString() === $query
        );
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function getEntityByFullTableName($tableName): ?Entity
    {
        return $this->entities->find($tableName,
            fn(Entity $entity, string $query): bool => $entity->getFullTableName()->toString() === $query
        );
    }

    /**
     * @return Text[]
     */
    public function getEntityNames(): array
    {
        return $this->entities->map(fn(Entity $entity): Text => $entity->getName())->toArray();
    }

    /**
     * Adds a new entity to this database.
     *
     * @param Entity $entity
     */
    public function addEntity(Entity $entity): void
    {
        if ($entity->getDatabase() !== $this) {
            $entity->setDatabase($this);
        }
        $this->entities->add($entity);
    }

    public function removeEntity(Entity $entity): void
    {
        $this->entities->remove($entity);
    }

    /**
     * Adds several entities at once.
     *
     * @param Entity[] $entities An array of Entity instances
     */
    public function addEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }
    }

    public function setSequences(array $sequences): void
    {
        $this->sequences->clear();
        $this->sequences->add(...$sequences);
    }

    public function getSequences(): ArrayList
    {
        return $this->sequences;
    }

    public function addSequence(string $sequence): void
    {
        $this->sequences->add($sequence);
    }

    public function hasSequence(string $sequence): bool
    {
        return $this->sequences->contains($sequence);
    }

    public function removeSequence(string $sequence): void
    {
        $this->sequences->remove($sequence);
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
     * @param Schema $schema The parent schema
     */
    protected function registerSchema(Schema $schema): void
    {
        $schema->addDatabase($this);
    }

    /**
     * Remove the parent schema
     *
     * @param Schema $schema
     */
    protected function unregisterSchema(Schema $schema)
    {
        $schema->removeDatabase($this);
    }

    /**
     * Adds a domain object to this database.
     *
     * @param Domain $domain
     */
    public function addDomain(Domain $domain): void
    {
        if (!$this->domains->contains($domain)) {
            $domain->setDatabase($this);
            $this->domains->set($domain->getName(), $domain);
        }
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
     * Returns the next behavior on all entities, ordered by behavior priority,
     * and skipping the ones that were already executed.
     *
     * @return Behavior
     */
    public function getNextEntityBehavior(): ?Behavior
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
     * @param Behavior $behavior
     */
    protected function registerBehavior(Behavior $behavior): void
    {
        $behavior->setDatabase($this);
    }

    /**
     * @param Behavior $behavior
     */
    protected function unregisterBehavior(Behavior $behavior): void
    {
        $behavior->setDatabase(null);
    }

    public function __toString(): string
    {
        return $this->toSql();
    }


    //@todo remove: use a template to render this inside the function that need it
    /**
     * @return string
     */
    public function toSql(): string
    {
        $entities = [];
        foreach ($this->getEntities() as $entity) {
            $columns = [];
            foreach ($entity->getFields() as $column) {
                $columns[] = sprintf(
                    "      %s %s %s %s %s %s",
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
                $fks[] = sprintf(
                    "      %s to %s.%s (%s => %s)",
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
                $indices[] = sprintf(
                    "      %s (%s)",
                    $index->getName(),
                    join(', ', $indexFields)
                );
            }

            $unices = [];
            foreach ($entity->getUnices() as $index) {
                $unices[] = sprintf(
                    "      %s (%s)",
                    $index->getName(),
                    join(', ', $index->getFields())
                );
            }

            $entityDef = sprintf(
                "  %s (%s):\n%s",
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

        return sprintf(
            "%s:\n%s",
            $this->getName() . ($this->getSchema() ? '.'. $this->getSchema() : ''),
            implode("\n", $entities)
        );
    }
}
