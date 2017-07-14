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
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Schema\Dumper\XmlDumper;
use phootwork\collection\Set;

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
    /** @var Set */
    private $databases;
    private $name;
    private $isInitialized;
    protected $generatorConfig;
    protected $schemas;
    protected $filename;
    protected $referenceOnly = true;
    protected $parent = null;

    /**
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * Creates a new instance for the specified database type.
     */
    public function __construct(PlatformInterface $platform = null)
    {
        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        $this->isInitialized = false;
        $this->databases = new Set();
        $this->schemas = new Set();
    }

    /**
     * Sets the generator configuration
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        $this->generatorConfig = $generatorConfig;
        
        if (!$this->platform) {
            $this->platform = $generatorConfig->createPlatformForDatabase();
        }
    }

    /**
     * Returns the generator configuration
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        if ($this->generatorConfig) {
            return $this->generatorConfig;
        }
        
        // walk up parent chain
        $parent = $this;
        while ($parent->getParent()) {
            $parent = $parent->getParent();
            if ($parent->getGeneratorConfig()) {
                return $parent->getGeneratorConfig();
            }
        }
    }

    /**
     * @return PlatformInterface
     */
    public function getPlatform(): PlatformInterface
    {
        return $this->platform;
    }

    /**
     * @param PlatformInterface $platform
     * @return $this
     */
    public function setPlatform(PlatformInterface $platform): Schema
    {
        $this->platform = $platform;
        return $this;
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
     * Sets the schema name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the schema name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the schema short name (without the '-schema' postfix).
     *
     * @return string
     */
    public function getShortName(): string
    {
        return str_replace('-schema', '', $this->name);
    }
    
    /**
     * Sets the parent schema (will make this an external schema)
     * 
     * @param Schema $schema
     * @return $this
     */
    public function setParent(Schema $schema): Schema
    {
        $this->parent = $schema;
        $this->parent->addExternalSchema($schema);
        return $this;
    }
    
    /**
     * Returns the parent schema
     * 
     * @return Schema
     */
    public function getParent(): ?Schema
    {
        return $this->parent;    
    }
    
    /**
     * Adds an external schema
     * 
     * @param Schema $schema
     * @return $this
     */
    public function addExternalSchema(Schema $schema): Schema
    {
        $this->schemas->add($schema);
        if (!$schema->isExternalSchema()) {
            $schema->setParent($this);
        }
        return $this;
    }
    
    /**
     * Removes an external schema (only relevant if this is an external schema)
     * 
     * @param Schema $schema
     * @return $this
     */
    public function removeExternalSchema(Schema $schema): Schema
    {
        $schema->setParent(null);
        $this->schemas->remove($schema);
        return $this;
    }
    
    /**
     * Returns whether this is an external schema
     * 
     * @return bool
     */
    public function isExternalSchema(): bool 
    {
        return null !== $this->parent;
    }
    
    /**
     * Retuns the root schema
     * 
     * @return Schema
     */
    public function getRootSchema(): Schema 
    {
        $parent = $this;
        while ($parent->getParent()) {
            $parent = $parent->getParent();
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
     * Returns whether or not this schema has multiple databases.
     *
     * @return boolean
     */
    public function hasMultipleDatabases(): bool
    {
        return count($this->databases) > 1;
    }

    /**
     * Returns the database according to the specified name.
     *
     * @param  string   $name
     * @param  boolean  $doFinalInitialization
     * @return Database
     */
    public function getDatabase($name = null, $doFinalInitialization = true)
    {
        // this is temporary until we'll have a clean solution
        // for packaging datamodels/requiring schemas
        if ($doFinalInitialization) {
            $this->doFinalInitialization();
        }

        if (null === $name) {
            return $this->databases[0];
        }

        return $this->databases->find(function(Database $db) {
            return $db->getName() === $name;
        });
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
        return $this->databases->search(function(Database $db) {
            return $db->getName() === $name;
        });
    }

    /**
     * Adds a database to the list and sets the Schema property to this
     * Schema. The database can be specified as a Database object or a
     * DOMNode object.
     *
     * @param  Database $database
     * @return $this
     */
    public function addDatabase(Database $database): Schema
    {
//         if ($database instanceof Database) {
//             $database->setParentSchema($this);
//             $this->databases[] = $database;

//             return $database;
//         }
        $database->setParentSchema($this);
        $this->databases->add($database);

        // XML attributes array / hash
//         $db = new Database();
//         $db->setParentSchema($this);
//         $db->setPlatform($this->getPlatform());
//         $db->loadMapping($database);

        return $this;
    }

    /**
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
