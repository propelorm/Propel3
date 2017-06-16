<?php
namespace Propel\Generator\Schema;

use phootwork\collection\Stack;
use phootwork\xml\XmlParserNoopVisitor;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\MappingModel;
use Propel\Generator\Model\ModelFactory;
use Propel\Generator\Model\NamingTool;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\VendorInfo;
use Propel\Generator\Manager\BehaviorManager;

/**
 * A parser class that visits nodes on a schema xml file
 * 
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 * @author Thomas Gossmann
 *
 */
class SchemaParserVisitor extends XmlParserNoopVisitor
{
    /** @var Stack */
    protected $tagStack;
    
    /** @var Database */
    protected $currDatabase;
    
    /** @var Entity */
    protected $currEntity;
    
    /** @var Field */
    protected $currField;
    
    /** @var Relation */
    protected $currRelation;
    
    /** @var Index */
    protected $currIndex;
    
    /** @var Unique */
    protected $currUnique;
    
    /** @var Behavior */ 
    protected $currBehavior;
    
    /** @var VendorInfo */
    protected $currVendor;
    
    /** @var Schema */
    protected $schema;
    
    /** @var BehaviorManager */
    protected $behaviorManager;
    
    /** @var SchemaReader */
    protected $reader;
    
    private $allowedTags = [
        'database',
        'entity',
        'table',
        'field',
        'column',
        'relation',
        'foreign-key',
        'index',
        'unique',
        'behavior',
        'vendor'
    ];
    
    public function __construct(SchemaReader $reader) {
        $this->reader = $reader;
        $this->behaviorManager = new BehaviorManager();
        $this->tagStack = new Stack();
    }
    
    public function setSchema(Schema $schema) {
        $this->schema = $schema;
        $this->behaviorManager->setGeneratorConfig($schema->getGeneratorConfig());
    }

    public function visitElementStart($name, $attributes, $line, $column) {
        $parentTag = $this->tagStack->peek();

        // root
        if (null === $parentTag) {
            switch ($name) {
                case 'database':
                    if ($this->schema->isExternalSchema()) {
                        $this->currDatabase = $this->schema->getRootSchema()->getDatabase();
                    } else {
                        $db = ModelFactory::createMappingModel($name, $attributes);
                        $this->schema->addDatabase($db);
                        $this->currDatabase = $db;
                    }
                    break;
                    
                default:
                    $this->throwInvalidTagException($name, $line, $column);
            }
        }
        
        // regonized parent tag
        else if (in_array($parentTag, $this->allowedTags)) {
            $methodName = 'visit' . NamingTool::toUpperCamelCase($parentTag);
            if (method_exists($this, $methodName)) {
                $this->$methodName($name, $attributes, $line, $column);
            }
        }
        
        // at this point, it must be an invalid tag
        else {
            $this->throwInvalidTagException($name, $line, $column);
        }
        
        $this->tagStack->push($name);
    }
    
    protected function visitDatabase($name, $attributes, $line, $column) {
        switch ($name) {
            case 'external-schema':
                $schema = new Schema();
                
                if (!$this->schema->isExternalSchema()) {
                    $isRefOnly = $attributes['referenceOnly'] ?? null;
                    $isRefOnly = null !== $isRefOnly ? ('true' === strtolower($isRefOnly)) : true;
                    $schema->setReferenceOnly($isRefOnly);
                }
                
                $filename = $attributes['filename'] ?? null;
                
                // very effective absolute path checking :D
                // if not absolute, make it relative to the current schema
                if ('/' !== $filename{0}) {
                    $filename = realpath(dirname($this->schema->getFilename()) . DIRECTORY_SEPARATOR . $filename);
                }
                $schema->setFilename($filename);
                $this->reader->parseExternal($schema);
                break;
                
            case 'domain':
                $domain = ModelFactory::createMappingModel($name, $attributes);
                $this->currDatabase->addDomain($domain);
                break;
                
            case 'table':
            case 'entity':
                // backwards compatibility
                if ('table' === $name) {
                    $name = 'entity';
                    $attributes['tableName'] = $attributes['name'];
                    
                    if (isset($attributes['phpName'])) {
                        $attributes['name'] = $attributes['phpName'];
                    } else {
                        $attributes['name'] = ucfirst(NamingTool::toCamelCase($attributes['tableName']));
                    }
                }
                
                $entity = ModelFactory::createMappingModel($name, $attributes);
                
                if ($this->schema->isExternalSchema()) {
                    $entity->setForReferenceOnly($this->schema->isExternalSchema());
                    //                         $this->currEntity->setPackage($this->currentPackage);
                }
                
                $this->currDatabase->addEntity($entity);
                $this->currEntity = $entity;
                break;
                
            case 'vendor':
                $this->parseVendor($this->currDatabase, $attributes);
                break;
                
            case 'behavior':
                $this->parseBehavior($this->currDatabase, $attributes);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    private function visitTable($name, $attributes, $line, $column) {
        $this->visitEntity($name, $attributes, $line, $column);
    }
    
    protected function visitEntity($name, $attributes, $line, $column) {
        switch ($name) {
            case 'column':
            case 'field':
                // backwards compatibility
                if ('column' === $name) {
                    $attributes['columnName'] = $attributes['name'];
                    if (isset($attributes['phpName'])) {
                        $attributes['name'] = $attributes['phpName'];
                    } else {
                        $attributes['name'] = NamingTool::toCamelCase($attributes['name']);
                    }
                }
                $field = new Field();
                $field->setEntity($this->currEntity);
                $field->loadMapping($attributes);
                $this->currEntity->addField($field);
                $this->currField = $field;
                break;
                
            case 'relation':
            case 'foreign-key':
                // backwards compatibility
                if ('foreign-key' === $name) {
                    $name = 'relation';
                    $attributes['target'] = ucfirst(NamingTool::toCamelCase($attributes['foreignTable']));
                    if (isset($attributes['phpName'])) {
                        $attributes['field'] = lcfirst($attributes['phpName']);
                    } else {
                        $attributes['field'] = NamingTool::toCamelCase($attributes['foreignTable']);
                    }
                }
                $relation = new Relation();
                $relation->setEntity($this->currEntity);
                $relation->loadMapping($attributes);
                $this->currRelation = $this->currEntity->addRelation($relation);
                break;
                
            case 'index':
                $this->currIndex = new Index();
                $this->currIndex->setEntity($this->currEntity);
                $this->currIndex->loadMapping($attributes);
                break;
                
            case 'unique':
                $this->currUnique = new Unique();
                $this->currUnique->setEntity($this->currEntity);
                $this->currUnique->loadMapping($attributes);
                break;
                
            case 'id-method-parameter':
                $id = ModelFactory::createMappingModel($name, $attributes);
                $this->currEntity->addIdMethodParameter($id);
                break;
                
            case 'behavior':
                $this->parseBehavior($this->currEntity, $attributes);
                break;
                
            case 'vendor':
                $this->parseVendor($this->currEntity, $attributes);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    private function visitColumn($name, $attributes, $line, $column) {
        $this->visitField($name, $attributes, $line, $column);
    }
    
    protected function visitField($name, $attributes, $line, $column) {
        switch ($name) {
            case 'inheritance':
                $inheritance = ModelFactory::createMappingModel($name, $attributes);
                $this->currField->addInheritance($inheritance);
                break;
                
            case 'vendor':
                $this->parseVendor($this->currField, $attributes);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    
    private function visitForeignKey($name, $attributes, $line, $column) {
        if ($name === 'reference') {
            $attributes['local'] = NamingTool::toCamelCase($attributes['local']);
            $attributes['foreign'] = NamingTool::toCamelCase($attributes['foreign']);
        }
        $this->visitRelation($name, $attributes, $line, $column);
    }
    
    protected function visitRelation($name, $attributes, $line, $column) {
        switch ($name) {
            case 'reference':
                $this->currRelation->addReference($attributes);
                break;
                
            case 'vendor':
                $this->parseVendor($this->currRelation, $attributes);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    protected function visitIndex($name, $attributes, $line, $column) {
        $parentTag = $this->tagStack->peek();
        switch ($name) {
            case 'index-column':
            case 'index-field':
                // backwards compatibility
                if ('index-column' === $parentTag) {
                    $attributes['name'] = NamingTool::toCamelCase($attributes['name']);
                }
                $this->currIndex->addField($attributes);
                break;
                
            case 'vendor':
                $this->parseVendor($this->currIndex, $attributes);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    protected function visitUnique($name, $attributes, $line, $column) {
        $parentTag = $this->tagStack->peek();
        switch ($name) {
            case 'unique-column':
            case 'unique-field':
                // backwards compatibility
                if ('unique-column' === $parentTag) {
                    $attributes['name'] = NamingTool::toCamelCase($attributes['name']);
                }
                $this->currUnique->addField($attributes);
                break;
                
            case 'vendor':
                $this->parseVendor($this->currUnique, $attributes);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    protected function visitBehavior($name, $attributes, $line, $column) {
        switch ($name) {
            case 'parameter':
                $this->currBehavior->addParameter($attributes);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    protected function visitVendor($name, $attributes, $line, $column) {
        switch ($name) {
            case 'parameter':
                $this->currVendor->setParameter($attributes['name'], $attributes['value']);
                break;
                
            default:
                $this->throwInvalidTagException($name, $line, $column);
        }
    }
    
    protected function parseVendor(MappingModel $parent, $attributes) {
        $vendor = ModelFactory::createMappingModel('vendor-info', $attributes);
        $parent->addVendorInfo($vendor);
        $this->currVendor = $vendor;
    }
    
    protected function parseBehavior($parent, $attributes) {
        $behavior = $this->behaviorManager->load($attributes['name']);
        $behavior->loadMapping($attributes);
        $parent->addBehavior($behavior);
        $this->currBehavior = $behavior;
    }
    
    public function visitElementEnd($name, $line, $column) {
        if ('index' === $name) {
            $this->currEntity->addIndex($this->currIndex);
        } else if ('unique' === $name) {
            $this->currEntity->addUnique($this->currUnique);
        }
        $this->tagStack->pop();
    }
    
    protected function throwInvalidTagException($name, $line, $column) {
        $location = '';
        if (null !== $this->schema->getFilename()) {
            $location .= sprintf('file %s,', $this->schema->getFilename());
        }
        
        $location .= sprintf('line %d', $line);
        if ($column) {
            $location .= sprintf(', column %d', $column);
        }
        
        throw new SchemaException(sprintf('Unexpected tag <%s> in %s', $name, $location));
    }
   
}