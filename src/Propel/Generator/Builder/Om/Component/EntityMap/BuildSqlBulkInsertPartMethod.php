<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Relation;

/**
 * Adds buildSqlBulkInsertPart method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildSqlBulkInsertPartMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = '
$params = [];
$placeholder = [];
$entityReader = $this->getPropReader();
        ';

        foreach ($this->getEntity()->getFields() as $field) {

            if (!$this->getEntity()->isAllowPkInsert() && $field->isAutoIncrement()) {
                continue;
            }

            $fieldName = $field->getName();
            $propertyName = $field->getName();

            if ($field->isImplementationDetail()) {
                continue;
            }


            $body .= "
//field:$fieldName
\$value = \$entityReader(\$entity, '$propertyName');";

            $default = 'NULL';
            if ($field->getDefaultValue() && $field->getDefaultValue()->isExpression()) {
                $default = $field->getDefaultValue()->getValue();
            }

            $default = var_export($default, true);

            $body .= "
\$value = \$this->propertyToDatabase(\$value, '{$fieldName}');
if (null !== \$value) {
    \$params['{$fieldName}'] = \$value;
    \$outgoingParams[] = \$value;
    \$placeholder[] = '?';
} else {
    \$placeholder[] = $default;
}
//end field:$fieldName
";
        }
        if ($this->getEntity()->hasRelations()) {
            $body .= "
\$relParams = [];
";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $className = $relation->getForeignEntity()->getFullClassName();
            $propertyName = $this->getRelationVarName($relation);

            $body .= "
//relation:$propertyName
\$foreignEntityReader = \$this->getClassPropReader('$className');
\$foreignEntity = \$entityReader(\$entity, '$propertyName');
";
            foreach ($relation->getFieldObjectsMapArray() as $map) {
                /** @var Field $localField */
                /** @var Field $foreignField */
                list ($localField, $foreignField) = $map;
                $foreignFieldName = $foreignField->getName();

                if ($foreignField->isImplementationDetail()) {
                    $foreignEntity = $relation->getForeignEntity();
                    foreach ($foreignEntity->getRelations() as $foreignRelation) {
                        if ($foreignRelation->getLocalField()->getName() === $foreignFieldName ) {
                            $body .= "
\$reader = \$this->getClassPropReader('{$foreignRelation->getForeignEntity()->getFullClassName()}');
\$forEnt = \$foreignEntityReader(\$foreignEntity, '{$this->getRelationVarName($foreignRelation)}');
\$value = \$reader(\$forEnt, '{$foreignRelation->getForeignField()->getName()}');";
                        }
                    }
                } else {
                    $body .= "
\$value = null;
if (\$foreignEntity) {
    \$value = \$foreignEntityReader(\$foreignEntity, '{$foreignFieldName}');
}";
                }
                $body .= "
if (!isset(\$relParams['{$localField->getName()}']) || null === \$relParams['{$localField->getName()}']) {
    \$relParams['{$localField->getName()}'] = \$value;
}";
            }

            $body .= "
//end relation:$propertyName
";
        }

        if ($this->getEntity()->hasRelations()) {
            $body .= "
foreach (\$relParams as \$relParam) {
    \$outgoingParams[] = \$relParam;
    \$placeholder[] = '?';
}
";
        }

        $body .= "
return '(' . implode(',', \$placeholder) . ')';
        ";

        $paramsParam = new PhpParameter('outgoingParams');
        $paramsParam->setPassedByReference(true);
        $paramsParam->setType('array');

        $this->addMethod('buildSqlBulkInsertPart')
            ->addSimpleParameter('entity')
            ->addParameter($paramsParam)
            ->setBody($body);
    }
}
