<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Relation;

/**
 * Adds getSnapshot method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetSnapshotMethod extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        $body = "
\$reader = \$this->getPropReader();
\$isset = \$this->getPropIsset();
\$snapshot = [];
";

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }

            $fieldName = $field->getName();
            if ($field->isLazyLoad()) {
                $body .= "
if (\$isset(\$entity, '$fieldName')){
    \$snapshot['$fieldName'] = \$this->propertyToSnapshot(\$reader(\$entity, '$fieldName'), '$fieldName');
}";
            } else {
                $body .= "\$snapshot['$fieldName'] = \$this->propertyToSnapshot(\$reader(\$entity, '$fieldName'), '$fieldName');\n";
            }
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $fieldName = $this->getRelationVarName($relation);
            $foreignEntityClass = $relation->getForeignEntity()->getFullName();
            $body .= "
if (\$isset(\$entity, '$fieldName') && \$foreignEntity = \$reader(\$entity, '$fieldName')) {
    \$foreignEntityReader = \$this->getConfiguration()->getEntityMap('$foreignEntityClass')->getPropReader();
";
            $emptyBody = '';

            foreach ($relation->getFieldObjectsMapArray() as $map) {
                /** @var Field $localField */
                /** @var Field $foreignField */
                list($localField, $foreignField) = $map;
                $relationFieldName = $localField->getName();
                $foreignFieldName = $foreignField->getName();
                $foreignEntity = $relation->getForeignEntity();

                if ($foreignEntity->getField($foreignFieldName)->isImplementationDetail()) {
                    //The field is both the primary and a foreign key of the foreign entity, so it's marked as
                    //implementation detail and it's not accessible by the property reader, so I should
                    //read the value from the related entity primary key

                    foreach ($foreignEntity->getRelations() as $rel) {
                        if ($rel->getLocalField()->getName() == $foreignFieldName) {
                            $relationEntityName = $rel->getForeignEntity()->getFullName();
                            $body .= "
    \$foreignForeignEntityReader = \$this->getClassPropReader('$relationEntityName');
    \$foreignForeignEntity = \$foreignEntityReader(\$foreignEntity, '{$this->getRelationVarName($rel)}');
    \$value = \$foreignForeignEntityReader(\$foreignForeignEntity, '{$rel->getForeignEntity()->getFirstPrimaryKeyField()->getName()}');";
                        }
                    }
                } else {
                    $body .= "
    \$value = \$foreignEntityReader(\$foreignEntity, '$foreignFieldName');";
                }

                $emptyBody .="
    \$snapshot['$relationFieldName'] = null;";
                $body .= "
    \$snapshot['$relationFieldName'] = \$value;";
            }

            $body .= "
    \$snapshot['$fieldName'] = \$foreignEntity;
} else {
    \$snapshot['$fieldName'] = null;
    $emptyBody
}
";
        }

        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            foreach ($crossRelation->getRelations() as $relation) {
                $varName = $this->getCrossRelationRelationVarName($relation);

                $body .= "
// cross relation to {$crossRelation->getForeignEntity()->getFullName()} via {$crossRelation->getMiddleEntity()->getFullName()}
if (\$isset(\$entity, '$varName')) {
    \$foreignEntities = \$reader(\$entity, '$varName') ?: [];
    if (\$foreignEntities instanceof \\Propel\\Runtime\\Collection\\Collection) {
        \$foreignEntities = \$foreignEntities->getData();
    }
    
    \$snapshot['$varName'] = \$foreignEntities;
}
";
            }
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $varName = $this->getRefRelationCollVarName($relation);

            $body .= "
// ref relation to {$relation->getForeignEntity()->getFullName()}
if (\$isset(\$entity, '$varName')) {
    \$foreignEntities = \$reader(\$entity, '$varName') ?: [];
    if (\$foreignEntities instanceof \\Propel\\Runtime\\Collection\\Collection) {
        \$foreignEntities = \$foreignEntities->getData();
    }
    
    \$snapshot['$varName'] = \$foreignEntities;
}
";
        }

        $body .= "
return \$snapshot;
";

        $this->addMethod('getSnapshot')
            ->addSimpleParameter('entity', 'object')
            ->setType('array')
            ->setBody($body);
    }
}
