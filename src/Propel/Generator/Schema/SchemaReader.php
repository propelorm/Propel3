<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Schema;

use phootwork\collection\Set;
use phootwork\xml\XmlParser;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Schema;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Config\QuickGeneratorConfig;

/**
 * A class that is used to parse an input xml schema file and creates a Schema
 * PHP object.
 *
 * @author Thomas Gossmann
 */
class SchemaReader
{
    private $parser;
    private $visitor;
    private $parsedFiles;
       
    public function __construct() {
        $this->visitor = new SchemaParserVisitor($this);
        $this->parser = new XmlParser();
        $this->parser->setOption(XmlParser::OPTION_CASE_FOLDING, 0);
        $this->parser->addVisitor($this->visitor);
        $this->parsedFiles = new Set();
    }

    /**
     * Parses a XML input file and returns a newly created and
     * populated Schema structure.
     *
     * @param  string $filename The input file to parse.
     *
     * @return Schema
     */
    public function parse(string $filename, GeneratorConfigInterface $config = null)
    {
        // we don't want infinite recursion
        if ($this->parsedFiles->contains($filename)) {
            return;
        }
        
        if (null === $config) {
            $config = new QuickGeneratorConfig();
        }

        $this->parsedFiles->clear();
        $schema = new Schema();
        $schema->setGeneratorConfig($config);
        $schema->setFilename($filename);
        
        $this->parseSchema($schema);
        
        return $schema;
    }
    
    public function parseExternal(Schema $schema) {
        $this->parseSchema($schema);
    }
    
    private function parseSchema(Schema $schema) {
        $filename = $schema->getFilename();
        if (!file_exists($filename)) {
            throw new SchemaException(sprintf('XML schema file (%s) no found.', $filename));
        }
        
        $this->visitor->setSchema($schema);

        
        // xsl transformation can happen here
                
        // validation can happen here (better would be `propel validate` command)
        
        $this->parser->parseFile($filename);
        $this->parsedFiles->add($filename);
    }
}
