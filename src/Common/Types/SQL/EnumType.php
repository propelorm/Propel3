<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Types\SQL;

use gossi\codegen\model\PhpConstant;
use Propel\Common\Types\AbstractType;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

/**
 * Class EnumType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class EnumType extends AbstractType implements BuildableFieldTypeInterface
{
    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     *
     * @return mixed
     */
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return mixed
     */
    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param AbstractBuilder $builder
     * @param Field $field
     */
    public function build(AbstractBuilder $builder, Field $field): void
    {
        if ($builder instanceof ObjectBuilder) {
            $types = [];

            foreach ($field->getValueSet() as $valueSet) {
                $constName = strtoupper($field->getName() . '_type_' . $valueSet);
                $constName = preg_replace('/[^a-zA-z0-9_]+/', '_', $constName);

                $types[] = 'self::' . $constName;
                $constant = PhpConstant::create($constName, $valueSet);
                $constant->setType('string');
                $builder->getDefinition()->setConstant($constant);
            }

            $all = '[' . implode(', ', $types) . ']';
            $allConstName = strtoupper($field->getName() . '_types');
            $constant = PhpConstant::create($allConstName, $all, true);
            $constant->setType('array|string[]');
            $builder->getDefinition()->setConstant($constant);
        }
    }
}
