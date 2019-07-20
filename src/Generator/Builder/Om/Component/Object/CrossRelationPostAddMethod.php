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

class CrossRelationPostAddMethod extends BuildComponent
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
        list ($signature, , $normalizedShortSignature) = $this->getCrossRelationAddMethodInformation($crossRelation, $relation);
        $relatedObjectClassName = $this->getRelationPhpName($relation, false);

        $body = "
//update cross relation collection        
\$crossEntity = null;

if (!{$normalizedShortSignature}->isNew()) {
    \$crossEntity = \Propel\Runtime\Configuration::getCurrentConfiguration()
        ->getRepository('{$crossRelation->getMiddleEntity()->getName()}')
        ->createQuery()
        ->filterBy{$this->getRelationPhpName($crossRelation->getIncomingRelation())}(\$this)
        ->filterBy{$relatedObjectClassName}({$normalizedShortSignature})
        ->findOne();
}

if (null === \$crossEntity) {    
    \$crossEntity = new {$crossRelation->getMiddleEntity()->getName()}();
    \$crossEntity->set{$relatedObjectClassName}({$normalizedShortSignature});
    \$crossEntity->set{$this->getRelationPhpName($crossRelation->getIncomingRelation())}(\$this);
}

\$this->add{$this->getRefRelationPhpName($crossRelation->getIncomingRelation())}(\$crossEntity);

//setup bidirectional relation
{$this->getBiDirectional($crossRelation)}        
";

        $method = $this->addMethod("postAdd$relatedObjectClassName", 'private')
            ->setDescription('Update the relations after adding a cross-relation object to the collection.')
            ->setBody($body);

        foreach ($signature as $parameter) {
            $method->addParameter($parameter);
        }
    }

    protected function getBiDirectional(CrossRelation $crossRelation)
    {
        $getterName = 'get' . $this->getRelationPhpName($crossRelation->getIncomingRelation(), true);
        $relation = $crossRelation->getOutgoingRelation();
        $varName = $this->getRelationVarName($relation);

        $body = "
// set the back reference to this object directly as using provided method either results
// in endless loop or in multiple relations
if (!\${$varName}->{$getterName}()->contains(\$this)) {
    \${$varName}->{$getterName}()->push(\$this);
}";

        return $body;
    }
}
