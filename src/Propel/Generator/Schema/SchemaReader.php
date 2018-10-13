<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Schema;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\Schema;
use phootwork\collection\Set;
use Propel\Generator\Schema\Loader\JsonSchemaLoader;
use Propel\Generator\Schema\Loader\PhpSchemaLoader;
use Propel\Generator\Schema\Loader\XmlSchemaLoader;
use Propel\Generator\Schema\Loader\YamlSchemaLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;

/**
 * A class that is used to load and parse input schema file and creates a Schema
 * PHP object.
 *
 * @author Thomas Gossmann
 */
class SchemaReader
{
    use SchemaParserTrait;

    /** @var Set */
    private $parsedFiles;

    /** @var GeneratorConfigInterface */
    protected $config;

    public function __construct(?GeneratorConfigInterface $config = null)
    {
        if (null === $config) {
            $config = new QuickGeneratorConfig();
        }

        $this->config = $config;
        $this->parsedFiles = new Set();
    }

    public function getGeneratorConfig(): GeneratorConfigInterface
    {
        return $this->config;
    }

    /**
     * Parses an input file and returns a newly created and
     * populated Schema structure.
     *
     * @param  string $filename The input file to parse.
     *
     * @return Schema
     * @throws \Exception
     */
    public function parse(string $filename): Schema
    {
        // we don't want infinite recursion
        if ($this->parsedFiles->contains($filename)) {
            return null;
        }

        $this->parsedFiles->clear();
        $schema = new Schema();
        $schema->setGeneratorConfig($this->getGeneratorConfig());
        $schema->setFilename($filename);
        
        $this->parseSchema($schema);
        
        return $schema;
    }

    /**
     * Parse a schema array and populates a Schema structure.
     *
     * @param Schema $schema
     * @throws \Exception
     */
    private function parseSchema(Schema $schema)
    {
        $filename = $schema->getFilename();
        $schemaArray = $this->loadSchema($filename);
        $this->parseDatabase($schemaArray, $schema);
        $this->parsedFiles->add($filename);
    }

    /**
     * Load a schema file. Supported formats are: xml, yaml, json, php.
     *
     * @param string $filename
     *
     * @return array
     * @throws \Exception
     */
    private function loadSchema(string $filename): array
    {
        $fileLocator = new FileLocator();
        $loaderResolver = new LoaderResolver([
            new YamlSchemaLoader($fileLocator),
            new XmlSchemaLoader($fileLocator),
            new JsonSchemaLoader($fileLocator),
            new PhpSchemaLoader($fileLocator)
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);
        $schemaArray = $delegatingLoader->load($filename);
        $processor = new Processor();
        $schemaConfiguration = new SchemaConfiguration();
        $schemaArray = $processor->processConfiguration($schemaConfiguration, $schemaArray);
        //normalize boolean values (i.e. for behaviors parameters)
        array_walk_recursive($schemaArray, function(&$element, $key){
            if (is_string($element)) {
                if ('true' === strtolower($element)) {
                    $element = true;
                } elseif ('false' === strtolower($element)) {
                    $element = false;
                }
            }
        });

        return $schemaArray;
    }
}
