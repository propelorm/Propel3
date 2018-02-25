<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Code;

use gossi\codegen\generator\ModelGenerator as GossiModelGenerator;
use gossi\codegen\generator\BuilderFactory;
use gossi\codegen\config\CodeGeneratorConfig;
use gossi\codegen\model\AbstractModel;
use gossi\codegen\generator\utils\Writer;

/**
 * @author Gregor Panek <gp@gregorpanek.de>
 */
class ModelGenerator extends GossiModelGenerator
{

    /**
     * @var CodeGeneratorConfig
     */
    protected $customConfig;

    /**
     * @var Writer
     */
    protected $customWriter;

    /**
     * @var BuilderFactory
     */
    protected $customFactory;

    /**
     * @param CodeGeneratorConfig|array $config
     */
    public function __construct($config = null)
    {
        if ($config === null) {
            $this->customConfig = new CodeGeneratorConfig(['generateDocblock' => false]);
        }

        if ($config instanceof CodeGeneratorConfig) {
            $this->customConfig = $config;
        }

        if (is_array($config)) {
            $this->customConfig = new CodeGeneratorConfig($config);
        }

        $this->customWriter = new Writer(['indentation_character' => ' ', 'indentation_size' => 4]);
        $this->customFactory = new BuilderFactory($this);
    }

    /**
     * @return CodeGeneratorConfig
     */
    public function getConfig()
    {
        return $this->customConfig;
    }
    
    /**
     * @return Writer
     */
    public function getWriter()
    {
        return $this->customWriter;
    }

    /**
     * Initalize Writer object with passed options
     * @param array $options
     */
    public function setWriterOptions(array $options): void
    {
        $this->customWriter = new Writer($options);
    }
    
    /**
     * @return BuilderFactory
     */
    public function getFactory()
    {
        return $this->customFactory;
    }

    /**
     * @param AbstractModel $model
     * @return string
     */
    public function generate(AbstractModel $model)
    {
        $this->customWriter->reset();
        
        $builder = $this->customFactory->getBuilder($model);
        $builder->build($model);

        return $this->customWriter->getContent();
    }
}
