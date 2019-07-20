<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\NamingTool;

/**
 * Adds populateObject method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PopulateObjectMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');

        $fields = $this->getEntity()->getFields();
        $fields->filter(function(Field $field) {
            return !$field->isLazyLoad();
        });

        $fieldCount = $fields->size();

        $body = "
";

        //first check primary key and first level cache
        $fullColumnNames = $columnNames = $camelNames = $fieldNames = $fieldTypes = [];
        $implementationDetail = [];
        $singlePk = 1 === count($this->getEntity()->getPrimaryKey());
        foreach ($this->getEntity()->getFields() as $idx => $field) {
            if ($field->isLazyLoad() || !$field->isPrimaryKey()) {
                continue;
            }

            if ($field->isImplementationDetail()) {
                $implementationDetail[$field->getName()] = true;
            }

            $fieldNames[$idx] = $field->getName();
            $fieldTypes[$idx] = $field->getType();
            $camelNames[$idx] = NamingTool::toCamelCase($field->getName());
            $columnNames[$idx] = $field->getColumnName();
            $fullColumnNames[$idx] = $field->getEntity()->getName(). '.' .$field->getColumnName();
        }

        $body .= "
if (EntityMap::TYPE_NUM === \$indexType) {
    //0
";
        foreach ($fieldNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$pk[] = \$this->databaseToProperty(\$row[\$offset + $idx], '$propName');";
        }

        $body .= "
} else if (EntityMap::TYPE_COLNAME === \$indexType) {
    //columnName
";
        foreach ($camelNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$pk[] = \$this->databaseToProperty(\$row['{$camelNames[$idx]}'], '$propName');";
        }

        $body .= "
} else if (EntityMap::TYPE_FIELDNAME === \$indexType) {
    //column_name
";
        foreach ($columnNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$pk[] = \$this->databaseToProperty(\$row['{$columnNames[$idx]}'], '$propName');";
        }

        $body .= "
} else if (EntityMap::TYPE_FULLCOLNAME === \$indexType) {
    //book.column_name
";
        foreach ($fullColumnNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$pk[] = \$this->databaseToProperty(\$row['{$fullColumnNames[$idx]}'], '$propName');";
        }

        $body .= "
}";

        if ($singlePk) {
            $body .= "
        \$pk = \$pk[0];
";
        }

        $body .= "
\$hashcode = json_encode(\$pk);
if (null === \$entity && \$object = \$this->getConfiguration()->getSession()->getInstanceFromFirstLevelCache('{$this->getEntity()->getFullName()}', \$hashcode)) {
    \$offset += $fieldCount;
    return \$object;
}
";

        $body .= "
\$writer = \$this->getPropWriter();
if (\$entity) {
    \$obj = \$entity;
} else {
    \$obj = \$this->createProxy();
}
\$obj->__duringInitializing__ = true;
\$objectValues = [];
";

        $fullColumnNames = $columnNames = $camelNames = $fieldNames = $fieldTypes = [];
        $implementationDetail = [];
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isLazyLoad()) {
                continue;
            }

            if ($field->isImplementationDetail()) {
                $implementationDetail[$field->getName()] = true;
            }

            $fieldNames[] = $field->getName();
            $fieldTypes[] = $field->getType();
            $camelNames[] = NamingTool::toCamelCase($field->getName());
            $columnNames[] = $field->getColumnName();
            $fullColumnNames[] = $field->getEntity()->getName(). '.' .$field->getColumnName();
        }

        $body .= "
if (EntityMap::TYPE_NUM === \$indexType) {
    //0
";
        foreach ($fieldNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$objectValues['$propName'] = \$this->databaseToProperty(\$row[\$offset + $idx], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$objectValues['$propName']);";
            }
        }

        $body .= "
} else if (EntityMap::TYPE_COLNAME === \$indexType) {
    //columnName
";
        foreach ($camelNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$objectValues['$propName'] = \$this->databaseToProperty(\$row['{$camelNames[$idx]}'], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$objectValues['$propName']);";
            }
        }

        $body .= "
} else if (EntityMap::TYPE_FIELDNAME === \$indexType) {
    //column_name
";
        foreach ($columnNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$objectValues['$propName'] = \$this->databaseToProperty(\$row['{$columnNames[$idx]}'], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$objectValues['$propName']);";
            }
        }

        $body .= "
} else if (EntityMap::TYPE_FULLCOLNAME === \$indexType) {
    //book.column_name
";
        foreach ($fullColumnNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$objectValues['$propName'] = \$this->databaseToProperty(\$row['{$fullColumnNames[$idx]}'], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$objectValues['$propName']);";
            }
        }

        $body .= "
}
";

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $this->getRelationVarName($relation);
            $className = $relation->getForeignEntity()->getFullName();

            $body .= "
//relation $relationName
\$exist = true;
";
            //$relationPkArguments => $id
            $relationPkArguments = [];
            foreach ($relation->getLocalFields() as $field) {
                $relationPkArguments[] = "\$objectValues['$field']";
            }
            $relationPkArguments = implode(', ', $relationPkArguments);

            foreach ($relation->getLocalFieldObjects() as $field) {
                $propName = $field->getName();
                $body .= "\$exist = \$exist && null !== \$objectValues['$propName'];";
            }

            $body .= "
if (\$exist) {
    \$relationProxy = \$this->getConfiguration()->getEntityMap('$className')->getReference($relationPkArguments);
//    \$relationProxyWriter = \$this->getConfiguration()->getEntityMap('$className')->getPropWriter();
";

//            foreach ($relation->getFieldObjectsMapping() as $mapping) {
//                /** @var Field $local */
//                $local = $mapping['local'];
//                /** @var Field $foreign */
//                $foreign = $mapping['foreign'];
//
//                $pkName = $foreign->getName();
//                $localName = $local->getName();
//                $body .= "
//    \$relationProxyWriter(\$relationProxy, '$pkName', \$objectValues['$localName']);";
//            }


            $body .= "
    \$writer(\$obj, '$relationName', \$relationProxy);
}
";
        }

        $body .= "
\$this->getConfiguration()->getSession()->snapshot(\$obj);
unset(\$obj->__duringInitializing__);
\$offset += $fieldCount;

return \$obj;
";

        $offsetParameter = new PhpParameter('offset');
        $offsetParameter->setPassedByReference(true);
        $offsetParameter->setType('integer');
        $offsetParameter->setDefaultValue(0);

        $this->addMethod('populateObject')
            ->addSimpleParameter('row', 'array')
            ->addParameter($offsetParameter)
            ->addSimpleParameter('indexType', 'string', PhpConstant::create('EntityMap::TYPE_NUM'))
            ->addSimpleParameter('entity', 'object', null)
            ->setBody($body);
    }
}
