<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

declare(strict_types=1);

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Generator\Builder\Om\AbstractBuilder;

class AddClassBehaviorBuilder extends AbstractBuilder
{
    public $overwrite = true;

    public function getFullClassName(string $injectNamespace = '', string $classPrefix = ''): string
    {
        return parent::getFullClassName() . 'FooClass';
    }

    /**
     * In this method the actual builder will define the class definition in $this->definition.
     *
     * @return false|null return false if this class should not be generated.
     */
    protected function buildClass()
    {
        $tableName = $this->getEntity()->getTableName();
        $this->getDefinition()->setDescription("Test class for Additional builder enabled on the '$tableName' table.");
    }
}
