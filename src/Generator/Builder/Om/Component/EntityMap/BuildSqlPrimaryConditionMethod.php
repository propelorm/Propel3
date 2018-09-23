<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds buildSqlPrimaryCondition method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildSqlPrimaryConditionMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = '
$entityReader = $this->getPropReader();
';
        $placeholder = [];

        foreach ($this->getEntity()->getPrimaryKey() as $field) {

            $fieldName = $field->getName();
            $propertyName = $field->getName();
            $placeholder[] = sprintf('%s = ?', $field->getColumnName());

            $body .= "
//$fieldName
\$value = null;";
            if ($field->isImplementationDetail()) {
                $body .= "
\$foreignEntity = null;";

                foreach ($field->getRelations() as $relation) {
                    /** @var Field $foreignField */
                    $foreignField = null;
                    foreach ($relation->getFieldObjectsMapArray() as $mapping) {
                        list($local, $foreign) = $mapping;
                        if ($local === $field) {
                            $foreignField = $foreign;
                        }
                    }

                    $relationEntityName = $relation->getForeignEntity()->getFullClassName();
                    $propertyName = $this->getRelationVarName($relation);
                    $body .= "
if (null === \$foreignEntity) {
    \$foreignEntity = \$entityReader(\$entity, '$propertyName');
    \$foreignEntityReader = \$this->getClassPropReader('$relationEntityName');
    \$value = \$foreignEntityReader(\$foreignEntity, '{$foreignField->getName()}');
}
";
                }
            } else {
                $body .= "
\$value = \$entityReader(\$entity, '$propertyName');
";
            }

            $body .= "
\$params[] = \$value;
";
        }


        $placeholder = var_export(implode(' AND ', $placeholder), true);
        $body .= "
return $placeholder;
        ";

        $paramsParam = new PhpParameter('params');
        $paramsParam->setPassedByReference(true);
        $paramsParam->setType('array');

        $this->addMethod('buildSqlPrimaryCondition')
            ->addSimpleParameter('entity')
            ->addParameter($paramsParam)
            ->setBody($body);
    }
}
