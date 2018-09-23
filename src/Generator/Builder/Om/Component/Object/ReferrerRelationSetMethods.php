<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all one-to-one referrer set methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ReferrerRelationSetMethods extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        $entity = $this->getEntity();

        foreach ($entity->getReferrers() as $refRelation) {
            if ($refRelation->isLocalPrimaryKey()) {
                //one-to-one
                $this->addRefGetMethod($refRelation);
            } else {
                $this->addRefGetCollectionMethod($refRelation);
            }
        }
    }

    /**
     * Adds the accessor (getter) method for getting an related object.
     *
     * @param Relation $relation
     */
    protected function addRefGetMethod(Relation $relation)
    {
        $varName = $this->getRefRelationVarName($relation);
        $setter =  'set' . $this->getRelationPhpName($relation);
        $foreignClassName = $this->useClass($relation->getEntity()->getFullClassName());

        $body = "
if (\$this->$varName !== \$$varName) {
    \$this->$varName = \$$varName;
    \$$varName->$setter(\$this);
}
";

        $internal = "\nMapped by fields " . implode(', ', $relation->getForeignFields());

        $methodName = 'set' . $this->getRefRelationPhpName($relation, false);
        $this->addMethod($methodName)
            ->addSimpleParameter($varName, $foreignClassName)
            ->setBody($body)
            ->setDescription("Sets the associated $foreignClassName object.$internal");
    }

    /**
     * Adds the accessor (getter) method for getting an related object.
     *
     * @param Relation $relation
     */
    protected function addRefGetCollectionMethod(Relation $relation)
    {
        $varName = $this->getRefRelationCollVarName($relation);
        $foreignClassName = $this->useClass($relation->getEntity()->getFullClassName());
        $setter = 'set' . $this->getRelationPhpName($relation);

        $body = "
//break relationship with old objects
foreach (\$this->$varName as \$item) {
    \$item->{$setter}(null);
}

\$this->$varName = \$$varName;

//establish bi-directional relationship with new objects
foreach (\$this->$varName as \$item) {
    \$item->{$setter}(\$this);
}
";

        $internal = "\nMapped by fields " . implode(', ', $relation->getForeignFields());

        $methodName = 'set' . $this->getRefRelationPhpName($relation, true);
        $this->addMethod($methodName)
            ->addSimpleParameter($varName)
            ->setBody($body)
            ->setDescription("Sets a collection of $foreignClassName objects.$internal");
    }
} 