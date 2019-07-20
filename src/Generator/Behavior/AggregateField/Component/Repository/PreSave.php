<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Behavior\AggregateField\Component\Repository;

use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class PreSave extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();
        $relation = $behavior->getRelation();

        $script = "
foreach (\$event->getEntities() as \$entity) {
    if (! \$entity instanceof {$this->getObjectClassName()}) {
        continue;
    }
    
    \$session = \$this->getConfiguration()->getSession();
    
    if (\$session->hasKnownValues(\$entity) && \$lastValues = \$session->getLastKnownValues(\$entity)) {";
        foreach ($relation->getLocalFieldObjects() as $field) {
            $script .= "
        if (null !== \$lastValues['{$field->getName()}']) {
            if (null === \$entity->get{$relation->getForeignEntity()->getName()}() || \$entity->get{$relation->getForeignEntity()->getName()}() !== \$lastValues['{$field->getName()}']) {
                /** @var \\{$this->getRepositoryClassNameForEntity($relation->getForeignEntity(), true)} \$relatedRepo */
                \$relatedRepo = \$this->getConfiguration()->getRepository('{$relation->getForeignEntity()->getFullName()}');
                \$relatedObjects = \$relatedRepo->createQuery()
                    ->filterByPrimaryKey(\$lastValues['{$field->getName()}'])
                    ->find();
                foreach (\$relatedObjects as \$relatedObject) {
                    \$relatedRepo->{$behavior->getParameter('update_method')}(\$relatedObject, false, true);
                }
            }
        }
    }
}
";

            return $script;
        }
    }
}
