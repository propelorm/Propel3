<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Behavior\AggregateField\Component\Repository;

use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class UpdateMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldBehavior $behavior */
        $behavior = $this->getBehavior();

        $conditions = array();
        if ($behavior->getParameter('condition')) {
            $conditions[] = $behavior->getParameter('condition');
        }

        $name = ucfirst($behavior->getField()->getName());
        $setter = 'set' . $name;

        $body = "
\$count = \$this->compute{$name}(\$entity);
\$count = \$decrease ? \$count - 1 : \$count;
\$entity->{$setter}(\$count);
\$this->persist(\$entity);
";

        $this->addMethod('update' . ucfirst($behavior->getField()->getName()))
            ->addSimpleDescParameter('entity', 'object', 'The entity object')
            ->addSimpleDescParameter('save', 'boolean', 'Save the entity immediately', false)
            ->addSimpleDescParameter('decrease', 'boolean', 'Whether to decrease of 1 the computed aggregate field. Useful for preSave hooks', false)
            ->setDescription("[AggregateField] Updates the aggregate field {$behavior->getField()->getName()}.")
            ->setBody($body)
        ;
    }
}