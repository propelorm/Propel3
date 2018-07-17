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
use Propel\Generator\Exception\LogicException;

/**
 * Class ModelFactory
 *
 * @author Thomas Gossmann
 */
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

    private $behavior = ['map' => [
        'name' => 'setName',
        'id'   => 'setId'
    ]];

    /** @var GeneratorConfigInterface */
    private $config;

    /**
     * ModelFactory constructor.
     *
     * @param null|GeneratorConfigInterface $config
     */
    public function __construct(?GeneratorConfigInterface $config = null)
    {
        $this->config = $config;
    }

    /**
     * @param GeneratorConfigInterface $config
     */
    public function setGeneratorConfig(GeneratorConfigInterface $config): void
    {
        $this->config = $config;
    }

    /**
     * @param array $attributes
     *
     * @return Vendor
     */
    public function createVendor(array $attributes): Vendor
    {
        return $this->load(new Vendor(), $attributes, $this->vendor);
    }

    /**
     * @param array $attributes
     *
     * @return Database
     */
    public function createDatabase(array $attributes): Database
    {
        $database = $this->load(new Database(), $attributes, $this->database);

        if (isset($attributes['platform']) && $this->config) {
            $platform = $this->config->createPlatform($attributes['platform']);
            if ($platform) {
                $database->setPlatform($platform);
            }
        }

        return $database;
    }

    /**
     * @param array $attributes
     *
     * @return Entity
     */
    public function createEntity(array $attributes): Entity
    {
        return $this->load(new Entity(), $attributes, $this->entity);
    }

    /**
     * @param array $attributes
     *
     * @return Behavior
     * @throws LogicException
     */
    protected function createBehavior(array $attributes): Behavior
    {
        $behavior = $this->load(new Behavior(), $attributes, $this->behavior);

        if (!$behavior->allowMultiple() && $attributes['id']) {
            throw new LogicException(sprintf('Defining an ID (%s) on a behavior which does not allow multiple instances makes no sense', $id));
        }
    }

    /**
     * @param $model
     * @param array $attributes
     * @param array $definition
     *
     * @return mixed
     */
    private function load($model, array $attributes, array $definition)
    {
        if (isset($definition['transform'])) {
            $this->transform($attributes, $definition['transform']);
        }

        if (isset($definition['map'])) {
            $model = $this->loadMapping($model, $attributes, $definition['map']);
        }

        return $model;
    }

    /**
     * @param array $attributes
     * @param array $transforms
     */
    private function transform(array &$attributes, array $transforms): void
    {
        foreach ($transforms as $key => $type) {
            if (isset($attributes[$key])) {
                $attributes[$key] = $this->transformValue($attributes[$key], $type);
            }
        }
    }

    /**
     * @param $value
     * @param $type
     *
     * @return bool
     */
    private function transformValue($value, $type): bool
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

    /**
     * @param $model
     * @param array $attributes
     * @param array $map
     *
     * @return mixed
     */
    private function loadMapping($model, array $attributes, array $map)
    {
        foreach ($map as $key => $method) {
            if (isset($attributes[$key]) && method_exists($model, $method)) {
                $model->$method($attributes[$key]);
            }
        }

        return $model;
    }
}
