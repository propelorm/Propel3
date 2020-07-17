<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Types;

use Susina\Codegen\Model\PhpMethod;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
abstract class AbstractType implements FieldTypeInterface
{
    /**
     * @param PhpMethod $method
     * @param Field $field
     */
    public function decorateGetterMethod(PhpMethod $method, Field $field): void
    {
        $varName = $field->getName();

        $body = <<<EOF
return \$this->{$varName};
EOF;
        $method->setBody($body);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     * @return mixed
     */
    public function propertyToSnapshot($value, FieldMap $fieldMap)
    {
        return $this->propertyToDatabase($value, $fieldMap);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     */
    public function snapshotToProperty($value, FieldMap $fieldMap)
    {
        return $this->databaseToProperty($value, $fieldMap);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return mixed
     */
    abstract public function propertyToDatabase($value, FieldMap $fieldMap);

    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     * @return mixed
     */
    abstract public function databaseToProperty($value, FieldMap $fieldMap);
}
