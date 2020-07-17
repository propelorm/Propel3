<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Types\SQL;

use phootwork\lang\ArrayObject;
use Propel\Common\Types\AbstractType;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;
use Susina\Codegen\Model\PhpConstant;

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
            $types = new ArrayObject();

            foreach ($field->getValueSet() as $valueSet) {
                $constName = $field->getName()->append("_type_$valueSet")->toSnakeCase()->toUpperCase();

                $types[] = 'self::' . $constName;
                $constant = PhpConstant::create((string) $constName, $valueSet);
                $constant->setType('string');
                $builder->getDefinition()->setConstant($constant);
            }

            $allConstName = $field->getName()->append('_types')->toUpperCase();
            $constant = PhpConstant::create((string) $allConstName, $types->join(', ')->ensureEnd(']')->ensureStart('[')->toString(), true);
            $constant->setType('array|string[]');
            $builder->getDefinition()->setConstant($constant);
        }
    }
}
