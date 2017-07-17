<?php
namespace Propel\Generator\Model;

use Propel\Generator\Config\GeneratorConfigInterface;

class ModelFactory
{
    private $truthy = ['true', 't', 'y', 'yes'];
    private $falsy = ['false', 'f', 'n', 'no'];

    private $database = [
        'transform' => [
            'identifierQuoting' => 'bool',
            'activeRecord' => 'bool'
        ],
        'map' => [
            'name' => 'setName',
            'baseClass' => 'setBaseClass',
            'defaultIdMethod' => 'setDefaultIdMethod',
            'heavyIndexing' => 'setHeavyIndexing',
            'identifierQuoting' => 'setIdentifierQuoting',
            'scope' => 'setScope',
            'defaultStringFormat' => 'setDefaultStringFormat',
            'activeRecord' => 'setActiveRecord'
        ]
    ];

    private $entity = [
        'transform' => [
            'activeRecord' => 'bool',
            'reloadOnInsert' => 'bool',
            'reloadOnUpdate' => 'bool',
            'allowPkInsert' => 'bool',
            'skipSql' => 'bool',
            'readOnly' => 'bool',
            'abstract' => 'bool',
            'repository' => 'boolsy',
            'identifierQuoting' => 'bool',
            'isCrossRef' => 'bool'
        ],
        'map' => [
            'name' => 'setName',
            'description' => 'setDescription',
            'tableName' => 'setTableName',
            'activeRecord' => 'setActiveRecord',
            'allowPkInsert' => 'setAllowPkInsert',
            'skipSql' => 'setSkipSql',
            'readOnly' => 'setReadOnly',
            'abstract' => 'setAbstract',
            'baseClass' => 'setBaseClass',
            'alias' => 'setAlias',
            'repository' => 'setRepository',
            'identifierQuoting' => 'setIdentifierQuoting',
            'reloadOnInsert' => 'setReloadOnInsert',
            'reloadOnUpdate' => 'setReloadOnUpdate',
            'isCrossRef' => 'setCrossRef',
            'defaultStringFormat' => 'setDefaultStringFormat',
        ]
    ];

    private $vendor = ['map' => ['type' => 'setType']];

    /** @var GeneratorConfigInterface */
    private $config;

    public function __construct(?GeneratorConfigInterface $config = null) {
        $this->config = $config;
    }

    public function setGeneratorConfig(GeneratorConfigInterface $config) {
        $this->config = $config;
    }

//     public static function createMappingModel($name, $attributes): MappingModelInterface {
//         $className = NamingTool::toUpperCamelCase($name);
//         $className = '\\Propel\\Generator\\Model\\' . $className;

//         if (class_exists($className)) {
//             $instance = new $className();
//             $instance->loadMapping($attributes);
//             return $instance;
//         }

//         return null; // or throw exception?
//     }

    private function load($model, array $attributes, array $definition) {
        if (isset($definition['transform'])) {
            $this->transform($attributes, $definition['transform']);
        }

        if (isset($definition['map'])) {
            $model = $this->loadMapping($model, $attributes, $definition['map']);
        }

        return $model;
    }

    private function transform(array &$attributes, array $transforms) {
        foreach ($transforms as $key => $type) {
            if (isset($attributes[$key])) {
                $attributes[$key] = $this->transformValue($attributes[$key], $type);
            }
        }
    }

    private function transformValue($value, $type)
    {
        switch ($type) {
            case 'bool':
                if (is_bool($value)) {
                    return $value;
                }

                if (is_numeric($value)) {
                    return (Boolean) $value;
                }

                return in_array(strtolower($value), $this->truthy, true);
                break;

            case 'boolsy':
                if (is_bool($value)) {
                    return $value;
                }

                if (is_numeric($value)) {
                    return (bool) $value;
                }

                if (in_array(strtolower($value), $this->truthy, true)) {
                    return true;
                }

                if (in_array(strtolower($value), $this->falsy, true)) {
                    return false;
                }

                return $value;
        }
    }

    private function loadMapping($model, array $attributes, array $map)
    {
        foreach ($map as $key => $method) {
            if (isset($attributes[$key]) && method_exists($model, $method)) {
                $model->$method($attributes[$key]);
            }
        }
        return $model;
    }

    public function createVendor(array $attributes): Vendor
    {
        return $this->load(new Vendor(), $attributes, $this->vendor);
    }

    public function createDatabase(array $attributes): Database
    {
        $database = $this->load(new Database(), $attributes, $this->database);

        if (isset($attributes['platform']) && $this->config) {
            $database->setPlatform($this->config->createPlatform($attributes['platform']));
        }

        return $database;
    }

    public function createEntity(array $attributes): Entity
    {
        return $this->load(new Entity(), $attrbutes, $this->entity);
    }
}
