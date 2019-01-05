<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all many-to-one referrer remove methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ReferrerRelationRemoveMethods extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        $entity = $this->getEntity();

        foreach ($entity->getReferrers() as $refRelation) {
            if ($refRelation->isLocalPrimaryKey()) {
                //one-to-one
                continue;
            }

            $this->addRefRemoveMethod($refRelation);
        }
    }

    /**
     * Adds the attributes used to store objects that have referrer fkey relationships to this object.
     *
     * @param Relation $refRelation
     */
    protected function addRefRemoveMethod(Relation $refRelation)
    {
        $varName = lcfirst($this->getRefRelationPhpName($refRelation));
        $className = $this->getObjectClassName();
        $methodName = 'remove' . ucfirst($varName);
        $colVarName = $this->getRefRelationCollVarName($refRelation);
        $relationClassName = $this->useClass($refRelation->getEntity()->getFullName());

        $body = "
if (\$this->{$colVarName} instanceof ObjectCollection) {
    \$this->{$colVarName}->removeObject(\${$varName});
    \${$varName}->set" . $this->getRelationPhpName($refRelation) . "(null);
}

return \$this;
";

        $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
        $this->addMethod($methodName)
            ->addSimpleParameter($varName, $relationClassName)
            ->setType($className . '|$this')
            ->setDescription("Deassociate a $relationClassName to this object")
            ->setBody($body);
    }
}
