<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all add* methods for crossRelations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationAdderMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        // many-to-many relationships
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addCrossAdd($crossRelation);
        }
    }

    protected function addCrossAdd(CrossRelation $crossRelation)
    {
        $relation = $crossRelation->getOutgoingRelation();
        $collName = $this->getRelationVarName($relation, true);

        $relatedObjectClassName = $this->getRelationPhpName($relation, false);
        $crossObjectClassName = $this->getClassNameFromEntity($relation->getForeignEntity());

        list ($signature, , $normalizedShortSignature) = $this->getCrossRelationAddMethodInformation($crossRelation, $relation);

        $body = <<<EOF
if (!\$this->{$collName}->contains({$normalizedShortSignature})) {
    \$this->{$collName}->push({$normalizedShortSignature});
    
    //Update relation collections
    \$this->postAdd$relatedObjectClassName($normalizedShortSignature);
}

return \$this;
EOF;

        $description = <<<EOF
Associate a $crossObjectClassName to this object
through the {$crossRelation->getMiddleEntity()->getFullClassName()} cross reference entity.
EOF;

        $method = $this->addMethod('add' . $relatedObjectClassName)
            ->setDescription($description)
            ->setType($this->getObjectClassName())
            ->setTypeDescription("The current object (for fluent API support)")
            ->setBody($body)
        ;

        foreach ($signature as $parameter) {
            $method->addParameter($parameter);
        }
    }
}
