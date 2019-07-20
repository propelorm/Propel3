<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Model\CrossRelation;

class CrossRelationPostRemoveMethod extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        // many-to-many relationships
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addCrossRelUpdate($crossRelation);
        }
    }

    protected function addCrossRelUpdate(CrossRelation $crossRelation)
    {
        $relation = $crossRelation->getOutgoingRelation();
        list ($signature, , $normalizedShortSignature, $phpDoc) = $this->getCrossRelationAddMethodInformation($crossRelation, $relation);
        $crossObjectName = '$' . $this->getRelationVarName($relation);
        $getterName = $this->getCrossRefRelationGetterName($crossRelation, $relation);
        $relatedObjectClassName = $this->getRelationPhpName($relation, false);

        $body ="
//update cross relation collection
\$crossEntities = \$this->get{$this->getRefRelationPhpName($crossRelation->getIncomingRelation(), true)}();
foreach (\$crossEntities as \$crossEntity) {
    if (\$crossEntity->get{$this->getRelationPhpName($relation)}() == {$normalizedShortSignature}) {
        \$this->remove{$this->getRefRelationPhpName($crossRelation->getIncomingRelation())}(\$crossEntity);
    }
}

//remove back reference
{$crossObjectName}->get{$getterName}()->removeObject(\$this);        
";
        $method = $this->addMethod("postRemove$relatedObjectClassName", 'private')
            ->setDescription('Update the relations after removing a cross-relation object from the collection.')
            ->setBody($body);

        foreach ($signature as $parameter) {
            $method->addParameter($parameter);
        }
    }
}
