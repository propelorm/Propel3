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
use Propel\Generator\Model\Parts\PlatformMutatorPart;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Schema\Dumper\XmlDumper;
use phootwork\collection\Set;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\SchemaPart;
use phootwork\collection\ArrayList;

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Schema
{
    use PlatformMutatorPart;
    use NamePart;
    use SchemaPart;

//     private $isInitialized;

    /** @var ArrayList */
    private $databases;

    protected $schemas;
    protected $filename;
    protected $referenceOnly = true;

    /**
     * Creates a new instance for the specified database type.
     */
    public function __construct(?PlatformInterface $platform = null)
    {
        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        // init
        $this->databases = new ArrayList();
        $this->schemas = new Set();

        // default values
//         $this->isInitialized = false;
    }

    protected function getSuperordinate()
    {
        return $this->schema;
    }


    /**
     * Sets the filename when reading this schema
     *
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): Schema
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Returns the filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @TODO what's this?
     * Returns the schema short name (without the '-schema' postfix).
     *
     * @return string
     */
    public function getShortName(): string
    {
        return str_replace('-schema', '', $this->name);
    }

    protected function registerSchema(Schema $schema)
    {
        $schema->addExternalSchema($this);
    }

    /**
     * Adds an external schema
     *
     * @param Schema $schema
     * @return $this
     */
    public function addExternalSchema(Schema $schema): Schema
    {
        if (!$this->schemas->contains($schema)) {
            $this->schemas->add($schema);
            $schema->setSchema($this);
        }
        return $this;
    }

    /**
     * Returns whether this is an external schema
     *
     * @return bool
     */
    public function isExternalSchema(): bool
    {
        return null !== $this->schema;
    }

    /**
     * Returns all external schema
     *
     * @return Schema[]
     */
    public function getExternalSchemas(): array
    {
        return $this->schemas->toArray();
    }

    /**
     * Returns the amount of external schemas
     *
     * @return int
     */
    public function getExternalSchemaSize(): int
    {
        return $this->schemas->size();
    }

    protected function unregisterSchema(Schema $schema)
    {
        $this->removeExternalSchema($schema);
    }

    /**
     * Removes an external schema (only relevant if this is an external schema)
     *
     * @param Schema $schema
     * @return $this
     */
    public function removeExternalSchema(Schema $schema): Schema
    {
        if ($this->schemas->contains($schema)) {
            $schema->setSchema(null);
            $this->schemas->remove($schema);
        }
        return $this;
    }

    /**
     * Retuns the root schema
     *
     * @return Schema
     */
    public function getRootSchema(): Schema
    {
        $parent = $this;
        while ($parent->getSchema()) {
            $parent = $parent->getSchema();
        }

        return $parent;
    }

    /**
     * Set whether this schema is only for reference (only relevant if this is an external schema)
     *
     * @param bool $referenceOnly
     * @return $this
     */
    public function setReferenceOnly(bool $referenceOnly): Schema
    {
        $this->referenceOnly = $referenceOnly;
        return $this;
    }

    /**
     * Returns whether this schema is for reference only
     *
     * @return bool
     */
    public function getReferenceOnly(): bool
    {
        return $this->referenceOnly;
    }

    /**
     * Adds a database to the list and sets the Schema property to this
     * Schema.
     *
     * @param Database $database
     * @return $this
     */
    public function addDatabase(Database $database): Schema
    {
        if (!$this->databases->contains($database)) {
            $this->databases->add($database);
            $database->setSchema($this);
        }

        return $this;
    }

    /**
     * Returns whether or not a database with the specified name exists in this
     * schema.
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasDatabase($name): bool
    {
        return $this->databases->search($name, function(Database $db, $query) {
            return $db->getName() === $query;
        });
    }

    /**
     * Returns whether or not this schema has multiple databases.
     *
     * @return bool
     */
    public function hasMultipleDatabases(): bool
    {
        return $this->databases->size() > 1;
    }

    /**
     * Returns the database according to the specified name or the first one.
     *
     * @param string $name
     * @return Database
     */
    public function getDatabase(?string $name = null): ?Database
    {
        // this is temporary until we'll have a clean solution
        // for packaging datamodels/requiring schemas
//         if ($doFinalInitialization) {
//             $this->doFinalInitialization();
//         }

        if ($this->databases->size() === 0) {
            return null;
        }

        if (null === $name) {
            return $this->databases->get(0);
        }

        return $this->databases->find($name, function(Database $db, $query) {
            return $db->getName() === $query;
        });
    }

    /**
     * Returns an array of all databases.
     *
     * The first boolean parameter tells whether or not to run the
     * final initialization process.
     *
     * @return Database[]
     */
    public function getDatabases()
    {
        // this is temporary until we'll have a clean solution
        // for packaging datamodels/requiring schemas
//         if ($doFinalInitialization) {
//             $this->doFinalInitialization();
//         }

        return $this->databases->toArray();
    }

    /**
     * Returns the amount of databases
     *
     * @return int
     */
    public function getDatabaseSize(): int
    {
        return $this->databases->size();
    }

    /**
     * Removes the database from this schema
     *
     * @param Database $database
     * @return $this
     */
    public function removeDatabase(Database $database): Schema
    {
        if ($this->databases->contains($database)) {
            $database->setSchema(null);
            $this->databases->remove($database);
        }
        return $this;
    }

    /**
     * @TODO externalize
     * Finalizes the databases initialization.
     *
     */
    public function doFinalInitialization()
    {
        if (!$this->isInitialized) {
            foreach ($this->databases as $database) {
                $database->doFinalInitialization();
            }
            $this->isInitialized = true;
        }
    }

    /**
     * @TODO
     * Merge other Schema objects together into this Schema object.
     *
     * @param Schema[] $schemas
     */
    public function joinSchemas(array $schemas)
    {
        foreach ($schemas as $schema) {
            foreach ($schema->getDatabases(false) as $addDb) {
                $addDbName = $addDb->getName();
                if ($this->hasDatabase($addDbName)) {
                    $db = $this->getDatabase($addDbName, false);
                    // temporarily reset database namespace to avoid double namespace decoration (see ticket #1355)
                    $namespace = $db->getNamespace();
                    $db->setNamespace(null);
                    // join tables
                    foreach ($addDb->getEntities() as $addEntity) {
                        if ($db->hasEntityByFullClassName($addEntity->getFullClassName())) {
                            throw new EngineException(sprintf('Duplicate entity found: %s.', $addEntity->getName()));
                        }
                        $db->addEntity($addEntity);
                    }
                    // join database behaviors
                    if ($addDb->getBehaviors()) {
                        foreach ($addDb->getBehaviors() as $addBehavior) {
                            if (!$db->hasBehavior($addBehavior->getId())) {
                                $db->addBehavior($addBehavior);
                            }
                        }
                    }
                    // restore the database namespace
                    $db->setNamespace($namespace);
                } else {
                    $this->addDatabase($addDb);
                }
            }
        }
    }

    /**
     * Returns the number of tables in all the databases of this Schema object.
     *
     * @return int
     */
    public function countEntities(): int
    {
        $nb = 0;
        foreach ($this->getDatabases() as $database) {
            $nb += $database->countEntities();
        }

        return $nb;
    }

    /**
     * Creates a string representation of this Schema.
     * The representation is given in xml format.
     *
     * @return string Representation in xml format
     */
    public function toString(): string
    {
        $dumper = new XmlDumper();

        return $dumper->dumpSchema($this);
    }

    /**
     * Magic string method
     *
     * @see toString()
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}

