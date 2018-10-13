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
use Propel\Generator\Manager\BehaviorManager;

/**
 * Class ModelFactory
 *
 * @author Thomas Gossmann
 * @author Cristiano Cinotti
 */
class ModelFactory
{
    private $database = ['map' => [
        'name' => 'setName',
        'baseClass' => 'setBaseClass',
        'defaultIdMethod' => 'setDefaultIdMethod',
        'heavyIndexing' => 'setHeavyIndexing',
        'identifierQuoting' => 'setIdentifierQuoting',
        'scope' => 'setScope',
        'defaultStringFormat' => 'setStringFormat',
        'activeRecord' => 'setActiveRecord'
    ]];

    private $entity = ['map' => [
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
        'defaultStringFormat' => 'setStringFormat',
        'heavyIndexing' => 'setHeavyIndexing'
    ]];

    private $field = ['map' => [
        'name' => 'setName',
        'primaryKey' => 'setPrimaryKey',
        'type' => 'setType',
        'description' => 'setDescription',
        'columnName' => 'setColumnName',
        'phpType' => 'setPhpType',
        'sqlType' => 'setSqlType',
        'size' => 'setSize',
        'scale' => 'setScale',
        'defaultValue' => 'setDefaultValue',
        'autoIncrement' => 'setAutoIncrement',
        'lazyLoad' => 'setLazyLoad',
        'primaryString' => 'setPrimaryString',
        'valueSet' => 'setValueSet',
        'inheritance' => 'setInheritanceType'
    ]];

    private $vendor = ['map' => ['type' => 'setType']];

    private $inheritance = ['map' => [
        'key' => 'setKey',
        'class' => 'setClassName',
        'package' => 'setPackage',
        'extends' => 'setAncestor'
    ]];

    private $relation = ['map'=> [
        'target' => 'setForeignEntityName',
        'field' => 'setField',
        'name' => 'setName',
        'refField' => 'setRefField',
        'refName' => 'setRefName',
        'onUpdate' => 'setOnUpdate',
        'onDelete' => 'setOnDelete',
        'defaultJoin' => 'setDefaultJoin',
        'skipSql' => 'setSkipSql'
    ]];

    /** @var GeneratorConfigInterface */
    private $config;

    /** @var BehaviorManager */
    private $behaviorManager;

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
     * @return Field
     */
    public function createField(array $attributes): Field
    {
        return $this->load(new Field(), $attributes, $this->field);
    }

    /**
     * @param array $attributes
     *
     * @return Inheritance
     */
    public function createInheritance(array $attributes): Inheritance
    {
        return $this->load(new Inheritance(), $attributes, $this->inheritance);
    }

    public function createRelation(array $attributes): Relation
    {
        $relation = $this->load(new Relation(), $attributes, $this->relation);

        if (count($attributes['references']) >0) {
            foreach ($attributes['references'] as $reference) {
                $relation->addReference($reference['local'], $reference['foreign']);
            }
        }

        return $relation;
    }

    /**
     * @param array $attributes
     *
     * @return Index
     */
    public function createIndex(array $attributes): Index
    {
        $index = new Index();
        $index->setName($attributes['name']);

        return $index;
    }

    /**
     * @param array $attributes
     *
     * @return Unique
     */
    public function createUnique(array $attributes): Unique
    {
        $unique = new Unique();
        $unique->setName($attributes['name']);

        return $unique;
    }

    /**
     * @param array $attributes
     *
     * @return IdMethodParameter
     */
    public function createIdMethodParameter(array $attributes): IdMethodParameter
    {
        $idMethodParam = new IdMethodParameter();
        $idMethodParam->setValue($attributes['value']);

        return $idMethodParam;
    }

    public function createBehavior(array $attributes): Behavior
    {
        $behavior = $this->getBehaviorManager()->create($attributes['name']);
        if (isset($attributes['parameters'])) {
            foreach ($attributes['parameters'] as $name => $value) {
                $behavior->setParameter($name, $value);
            }
        }

        return $behavior;
    }

    /**
     * @return BehaviorManager
     */
    protected function getBehaviorManager(): BehaviorManager
    {
        if (null === $this->behaviorManager) {
            $this->behaviorManager = new BehaviorManager($this->config);
        }

        return $this->behaviorManager;
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
        if (isset($definition['map'])) {
            $model = $this->loadMapping($model, $attributes, $definition['map']);
        }

        return $model;
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
