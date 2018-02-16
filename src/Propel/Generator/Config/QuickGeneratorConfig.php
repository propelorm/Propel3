<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Config;

use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Entity;
use Propel\Generator\Util\BehaviorLocator;

/**
 * Class QuickGeneratorConfig
 *
 * Simple generator config class. It's usually used with QuickBuilder, for testing purpose
 */
class QuickGeneratorConfig extends GeneratorConfig implements GeneratorConfigInterface
{
    /**
     * @var BehaviorLocator
     */
    protected $behaviorLocator = null;

    /**
     * QuickGeneratorConfig constructor.
     *
     * @param array $extraConf
     */
    public function __construct(array $extraConf = [])
    {
        if (null === $extraConf) {
            $extraConf = [];
        }

        //Creates a GeneratorConfig based on Propel default values plus the following
        $configs = [
            'propel' => [
                'database' => [
                    'connections' => [
                        'default' => [
                            'adapter' => 'sqlite',
                            'classname' => 'Propel\Runtime\Connection\DebugPDO',
                            'dsn' => 'sqlite::memory:',
                            'user' => '',
                            'password' => ''
                        ]
                    ]
                ],
                'runtime' => [
                    'defaultConnection' => 'default',
                    'connections' => ['default']
                ],
                'generator' => [
                    'defaultConnection' => 'default',
                    'connections' => ['default']
                ]
            ]
        ];

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
    public function getConfiguredBuilder(Entity $entity, string $type): DataModelBuilder
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
    public function getConfiguredPluralizer(): PluralizerInterface
    {
        return new StandardEnglishPluralizer();
    }

    public function getBehaviorLocator(): BehaviorLocator
    {
        if (!$this->behaviorLocator) {
            $this->behaviorLocator = new BehaviorLocator($this);
        }

        return $this->behaviorLocator;
    }
}
