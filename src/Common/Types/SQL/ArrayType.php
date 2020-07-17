<?php declare(strict_types=1);
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

/**
 * Class ArrayType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ArrayType extends AbstractType implements BuildableFieldTypeInterface
{
    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return array|string
     */
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        if (null !== $value) {
            $value = ltrim($value, '| ');
            $value = rtrim($value, ' |');
        }

        return $value ? explode(' | ', $value) : [];
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return mixed|null|string
     */
    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_array($value)) {
            return $value ? '| ' . implode(' | ', $value) . ' |' : null;
        }

        return $value;
    }

    /**
     * @param AbstractBuilder $builder
     * @param Field $field
     */
    public function build(AbstractBuilder $builder, Field $field): void
    {
        if ($builder instanceof ObjectBuilder) {
            $property = $builder->getDefinition()->getProperty($field->getName());

            if (!$property->hasValue()) {
                $property->setExpression('[]');
            }
        }
    }
}
