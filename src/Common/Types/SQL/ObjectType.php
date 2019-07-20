<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

/**
 * Class ObjectType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ObjectType extends AbstractType
{
    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     *
     * @return mixed|null
     */
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return $value ? unserialize($value) : null;
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
        if (is_string($value) && $value) {
            return $value;
        }

        return is_object($value) ? serialize($value) : null;
    }
}
