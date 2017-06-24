<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Common\Types\SQL;

use gossi\codegen\model\PhpMethod;
use Propel\Common\Types\AbstractType;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Map\FieldMap;
use Propel\Runtime\Util\PropelDateTime;

/**
 * Class DateTimeType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class DateTimeType extends AbstractType implements BuildableFieldTypeInterface
{
    /**
     * {@inheritdoc}
     *
     * @param PhpMethod $method
     * @param Field $field
     */
    public function decorateGetterMethod(PhpMethod $method, Field $field): void
    {
        $varName = $field->getName();
        $method->addSimpleParameter('format', 'string', null);

        $body = <<<EOF
if (\$format && \$this->{$varName} instanceof \\DateTime) {
    return \$this->{$varName}->format(\$format);
}

return \$this->{$varName};
EOF;
        $method->setBody($body);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return string
     */
    public function propertyToDatabase($value, FieldMap $fieldMap):? string
    {
        if ($value instanceof \DateTime) {
            $format = 'U';

            $adapter = $fieldMap->getEntity()->getAdapter();

            if ($fieldMap->getType() === PropelTypes::DATE) {
                $format = $adapter->getDateFormatter();
            } elseif ($fieldMap->getType() === PropelTypes::TIME) {
                $format = $adapter->getTimeFormatter();
            } elseif ($fieldMap->getType() === PropelTypes::TIMESTAMP) {
                $format = $adapter->getTimestampFormatter();
            }

            return $value->format($format);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     *
     * @return \DateTime|null
     */
    public function databaseToProperty($value, FieldMap $fieldMap):? \DateTime
    {
        if (!($value instanceof \DateTime)) {
            $value = PropelDateTime::newInstance($value);
        }

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
            $property = $builder->getDefinition()->getProperty($field->getName());

            if ($field->hasDefaultValue()) {

                if ($field->getDefaultValue()->isExpression() && strtoupper($field->getDefaultValue()->getValue()) === 'CURRENT_TIMESTAMP') {
                    $property->unsetExpression();
                    $property->unsetValue();
                }
            }
        }
    }
}
