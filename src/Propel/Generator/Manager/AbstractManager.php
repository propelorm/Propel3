<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Manager;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;
use Propel\Generator\Schema\SchemaReader;
use Propel\Runtime\Map\DatabaseMap;

/**
 * An abstract base Propel manager to perform work related to the schema
 * file.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@zenplex.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
abstract class AbstractManager
{
    /**
     * Data models that we collect. One from each schema file.
     */
    protected $dataModels = [];

    /**
     * @var Database[]
     */
    protected $databases;

    /**
     * Map of data model name to database name.
     * Should probably stick to the convention
     * of them being the same but I know right now
     * in a lot of cases they won't be.
     */
    protected $dataModelDbMap;

    /**
     * DB encoding to use for SchemaReader object
     */
    protected $dbEncoding = 'UTF-8';

    /**
     * Gets list of all used schemas
     *
     * @var array
     */
    protected $schemas = [];

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @var \Closure
     */
    private $loggerClosure = null;

    /**
     * Have data models been initialized?
     *
     * @var boolean
     */
    private $dataModelsLoaded = false;

    /**
     * An initialized GeneratorConfig object.
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * Returns the list of schemas.
     *
     * @return array
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Sets the schemas list.
     *
     * @param array
     */
    public function setSchemas(array $schemas): void
    {
        $this->schemas = $schemas;
    }

    /**
     * Sets the working directory path.
     *
     * @param string $workingDirectory
     */
    public function setWorkingDirectory(string $workingDirectory): void
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * Returns the working directory path.
     *
     * @return string
     */
    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * Returns the data models that have been
     * processed.
     *
     * @return Schema[]
     */
    public function getDataModels(): array
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModels;
    }

    /**
     * Returns the data model to database name map.
     *
     * @return array
     */
    public function getDataModelDbMap(): array
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModelDbMap;
    }

    /**
     * @return Database[]
     */
    public function getDatabases(): array
    {
        if (null === $this->databases) {
            /** @var Database[] $databases */
            $databases = [];
            foreach ($this->getDataModels() as $dataModel) {
                foreach ($dataModel->getDatabases() as $database) {
                    if (!isset($databases[$database->getName()])) {
                        $databases[$database->getName()] = $database;
                    } else {
                        $entities = $database->getEntities();
                        // Merge entities from different schema.xml to the same database
                        foreach ($entities as $entity) {
                            if (!$databases[$database->getName()]->hasEntityByName($entity->getName())) {
                                $databases[$database->getName()]->addEntity($entity);
                            }
                        }
                    }
                }
            }
            $this->databases = $databases;
        }

        return $this->databases;
    }

    /**
     * @param  string $name
     * @return Database|null
     */
    public function getDatabase($name):? Database
    {
        $dbs = $this->getDatabases();
        return @$dbs[$name];
    }

    /**
     * Sets the current target database encoding.
     *
     * @param string $encoding Target database encoding
     */
    public function setDbEncoding(string $encoding): void
    {
        $this->dbEncoding = $encoding;
    }

    /**
     * Sets a logger closure.
     *
     * @param \Closure $logger
     */
    public function setLoggerClosure(\Closure $logger): void
    {
        $this->loggerClosure = $logger;
    }

    /**
     * Returns all matching XML schema files and loads them into data models for
     * class.
     */
    protected function loadDataModels(): void
    {
        $schemas = [];
        $totalNbEntities   = 0;
        $dataModelFiles  = $this->getSchemas();

        if (empty($dataModelFiles)) {
            throw new BuildException('No schema files were found (matching your schema fileset definition).');
        }
        // Make a transaction for each file
        foreach ($dataModelFiles as $schema) {
            $dmFilename = $schema->getPathName();
            $this->log('Processing: ' . $schema->getFileName());

            $schemaReader = new SchemaReader($this->getGeneratorConfig());
            $schema = $schemaReader->parse($dmFilename);

            $nbEntities = $schema->getDatabase()->countEntities();
            $totalNbEntities += $nbEntities;

            $this->log(sprintf('  %d entities processed successfully', $nbEntities));

            $schema->setName($dmFilename);
            $schemas[] = $schema;
        }

        $this->log(sprintf('%d entities found in %d schema files.', $totalNbEntities, count($dataModelFiles)));

        if (empty($schemas)) {
            throw new BuildException('No schema files were found (matching your schema fileset definition).');
        }

        foreach ($schemas as $schema) {
            // map schema filename with database name
            $this->dataModelDbMap[$schema->getName()] = $schema->getDatabase(null, false)->getName();
        }

        if (count($schemas) > 1) {
            $schema = $this->joinDataModels($schemas);
            $this->dataModels = [$schema];
        } else {
            $this->dataModels = $schemas;
        }

        foreach ($this->dataModels as $schema) {
            $schema->getPlatform()->doFinalInitialization($schema);
        }

        $this->dataModelsLoaded = true;
    }

    /**
     * Joins the datamodels collected from schema.xml files into one big datamodel.
     * We need to join the datamodels in this case to allow for foreign keys
     * that point to entities in different packages.
     *
     * @param  array  $schemas
     * @return Schema
     */
    protected function joinDataModels(array $schemas): Schema
    {
        $mainSchema = array_shift($schemas);
        $mainSchema->joinSchemas($schemas);

        return $mainSchema;
    }

    /**
     * Returns the GeneratorConfig object for this manager or creates it
     * on-demand.
     *
     * @return GeneratorConfigInterface
     */
    protected function getGeneratorConfig(): GeneratorConfigInterface
    {
        return $this->generatorConfig;
    }

    /**
     * Sets the GeneratorConfigInterface implementation.
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig): void
    {
        $this->generatorConfig = $generatorConfig;
    }

    protected function log($message)
    {
        if (null !== $this->loggerClosure) {
            $closure = $this->loggerClosure;
            $closure($message);
        }
    }
}
