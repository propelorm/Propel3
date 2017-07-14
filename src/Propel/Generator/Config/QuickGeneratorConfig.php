<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Entity;
use Propel\Generator\Manager\BehaviorManager;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Common\Pluralizer\PluralizerInterface;

class QuickGeneratorConfig extends GeneratorConfig implements GeneratorConfigInterface
{
    /**
     * @var BehaviorManager
     */
    protected $behaviorManager = null;

    public function __construct($extraConf = array())
    {
        if (null === $extraConf) {
            $extraConf = array();
        }

        //Creates a GeneratorConfig based on Propel default values plus the following
        $configs = array(
            'propel' => array(
                'database' => array(
                    'connections' => array(
                        'default' => array(
                            'adapter' => 'sqlite',
                            'classname' => 'Propel\Runtime\Connection\DebugPDO',
                            'dsn' => 'sqlite::memory:',
                            'user' => '',
                            'password' => ''
                        )
                    )
                ),
                'runtime' => array(
                    'defaultConnection' => 'default',
                    'connections' => array('default')
                ),
                'generator' => array(
                    'defaultConnection' => 'default',
                    'connections' => array('default')
                )
            )
        );

        $configs = array_replace_recursive($configs, $extraConf);
        $this->process($configs);
    }

    /**
     * Gets a configured data model builder class for specified entity and based
     * on type ('ddl', 'sql', etc.).
     *
     * @param  Entity $entity
     * @param  string $type
     * @return DataModelBuilder
     */
    public function getConfiguredBuilder(Entity $entity, $type)
    {
        $class = $this->getConfigProperty('generator.objectModel.builders.' . $type);

        if (null === $class) {
            throw new InvalidArgumentException("Invalid data model builder type `$type`");
        }

        $builder = new $class($entity);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Returns a configured Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getConfiguredPluralizer()
    {
        return new StandardEnglishPluralizer();
    }

    public function getBehaviorManager()
    {
        if (!$this->behaviorManager) {
            $this->behaviorManager = new BehaviorManager($this);
        }

        return $this->behaviorManager;
    }
}
