<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Behavior\AggregateField\Component\RelatedRepository;

use gossi\codegen\model\PhpProperty;
use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Attribute extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();

        $relationName = $behavior->getRelationName();
        $variableName = $relationName . ucfirst($behavior->getParameter('aggregate_name'));

//        $relationName = $behavior->getRelationName();
        $relatedClass = $behavior->getForeignEntity()->getFullName();
//        $aggregateName = $behavior->getParameter('aggregate_name');

        $property = new PhpProperty("afCache{$variableName}");
        $property->setType($relatedClass . '[]');
        $property->setDescription('[AggregateField-related]');
        $property->setVisibility('protected');
        $this->getDefinition()->setProperty($property);
    }
}
