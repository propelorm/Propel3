<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

class ArrayType extends AbstractType implements BuildableFieldTypeInterface
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        $value = ltrim($value, '| ');
        $value = rtrim($value, ' |');

        return $value ? explode(' | ', $value) : [];
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_array($value)) {
            return $value ? '| ' . implode(' | ', $value) . ' |' : null;
        }

        return $value;
    }

    public function build(AbstractBuilder $builder, Field $field)
    {
        if ($builder instanceof ObjectBuilder) {
            $property = $builder->getDefinition()->getProperty($field->getName());

            if (!$property->hasValue()) {
                $property->setExpression('[]');
            }
        }
    }
}
