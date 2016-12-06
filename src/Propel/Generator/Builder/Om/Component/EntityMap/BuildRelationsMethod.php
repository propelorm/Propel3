<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 * Adds buildRelations method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildRelationsMethod extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        $body = "";

        $this->getDefinition()->declareUse('Propel\Runtime\Map\RelationMap');

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = var_export($this->getRelationVarName($relation), true);
            $target = var_export($relation->getForeignEntity()->getFullClassName(), true);
            $columnMapping = var_export($relation->getLocalForeignMapping(), true);

            $onDelete = $relation->hasOnDelete() ? "'" . $relation->getOnDelete() . "'" : 'null';
            $onUpdate = $relation->hasOnUpdate() ? "'" . $relation->getOnUpdate() . "'" : 'null';

            $type = 'RelationMap::MANY_TO_ONE';
            if ($relation->isLocalPrimaryKey()) {
                $type = 'RelationMap::ONE_TO_ONE';
            }

            $body .= "
\$this->addRelation($relationName, $target, $type, $columnMapping, $onDelete, $onUpdate);";
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $relationName = var_export($this->getRefRelationCollVarName($relation), true);
            $target = var_export($relation->getEntity()->getFullClassName(), true);
            $columnMapping = var_export($relation->getLocalForeignMapping(), true);

            $onDelete = $relation->hasOnDelete() ? "'" . $relation->getOnDelete() . "'" : 'null';
            $onUpdate = $relation->hasOnUpdate() ? "'" . $relation->getOnUpdate() . "'" : 'null';

            $type = "RelationMap::ONE_TO_" . ($relation->isLocalPrimaryKey() ? "ONE" : "MANY");

            $refName = var_export($this->getRelationVarName($relation), true);

            $body .= "
//ref relation
\$this->addRefRelation($relationName, $target, $type, $columnMapping, $onDelete, $onUpdate, $refName);";
        }

        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {

            $relation = $crossRelation->getOutgoingRelation();
            $relationName = var_export($this->getRelationVarName($relation, true), true);
            $refName = $relationName;

            $target = var_export($crossRelation->getForeignEntity()->getFullClassName(), true);
            
            $onDelete = $crossRelation->getIncomingRelation()->hasOnDelete() ? "'" . $crossRelation->getIncomingRelation()->getOnDelete() . "'" : 'null';
            $onUpdate = $crossRelation->getIncomingRelation()->hasOnUpdate() ? "'" . $crossRelation->getIncomingRelation()->getOnUpdate() . "'" : 'null';

            $fieldMapping = [];
            foreach ($crossRelation->getRelations() as $relation) {
                $fieldMapping[$relation->getField()] = array_merge($relation->getLocalForeignMapping(), $fieldMapping);
            }

            $mapping = [
                'via' => $crossRelation->getMiddleEntity()->getFullClassName(),
                'viaTable' => $crossRelation->getMiddleEntity()->getFQTableName(),
                'isImplementationDetail' => $crossRelation->getMiddleEntity()->isImplementationDetail(),
                'fieldMappingIncomingName' => $crossRelation->getIncomingRelation()->getField(),
                'fieldMappingIncoming' => $crossRelation->getIncomingRelation()->getLocalForeignMapping(),
                'fieldMappingOutgoing' => $fieldMapping,
                'fieldMappingPrimaryKeys' => $crossRelation->getUnclassifiedPrimaryKeyNames(),
            ];

            $mapping = var_export($mapping, true);
            $polymorphic = var_export($crossRelation->isPolymorphic(), true);
            $body .= "
//cross relation
\$this->addRelation($relationName, $target, RelationMap::MANY_TO_MANY, $mapping, $onDelete, $onUpdate, $refName, $polymorphic);";
        }

        $this->addMethod('buildRelations')
            ->setBody($body);
    }
}