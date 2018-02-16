<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all setter methods for crossRelations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationSetterMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addCrossFKSet($crossRelation);
        }
    }

    /**
     * Adds the standard variant for cross relation getters.
     *
     * @param CrossRelation $crossRelation
     */
    protected function addCrossFKSet(CrossRelation $crossRelation)
    {
        $this->addNormalCrossRelationSetter($crossRelation);
    }

//    /**
//     * Adds the active record variant of cross relation getters.
//     *
//     * @param CrossRelation $crossRelation
//     */
//    protected function addActiveCrossFKGet(CrossRelation $crossRelation)
//    {
//        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {
//            $this->addActiveCombinedCrossRelationGetter($crossRelation);
//        } else {
//            $this->addActiveNormalCrossRelationGetter($crossRelation);
//        }
//    }

    protected function addNormalCrossRelationSetter(CrossRelation $crossRelation)
    {
        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();
        $relation = $crossRelation->getOutgoingRelation();

        $relatedName = $this->getRelationPhpName($relation, true);
        $relatedObjectClassName = $this->useClass($relation->getForeignEntity()->getFullClassName());

        $collName = $this->getCrossRelationRelationVarName($relation);

        $remover = 'remove' . $this->getRelationPhpName($relation);
        $post = $this->getRelationPhpName($crossRelation->getOutgoingRelation(), false);

    $body = "
if (\$this->$collName === $$collName) {
    //If `$$collName` passed by reference, and it refers to `\$this->$collName`, update relations only
    foreach ($$collName as \$obj) {
        \$this->postRemove$post(\$obj);
        \$this->postAdd$post(\$obj);
    }
    
    return \$this;
}

//break relationship with old objects
foreach (\$this->$collName as \$item) {
    \$this->{$remover}(\$item);
}

//If the collection is empty, reset index
if (\$this->{$collName}->isEmpty()) {
    \$this->{$collName}->exchangeArray([]);
}

foreach (\$$collName as \$item) {
    \$this->add{$this->getRelationPhpName($relation)}(\$item);
}

return \$this;";

        $description = <<<EOF
Sets a collection of $relatedObjectClassName objects related by a many-to-many relationship
to the current object by way of the $crossRefEntityName cross-reference entity.
EOF;

        $this->addMethod('set' . $relatedName)
            ->addSimpleParameter($collName)
            ->setType($this->getObjectClassName())
            ->setTypeDescription("The current object (for fluent API support)")
            ->setDescription($description)
            ->setBody($body);
    }
}