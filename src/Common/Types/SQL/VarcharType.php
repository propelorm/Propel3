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

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

/**
 * Class VarcharType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class VarcharType extends AbstractType
{
    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     *
     * @return null|string
     */
    public function databaseToProperty($value, FieldMap $fieldMap):? string
    {
        return null === $value ? null : (string) $value;
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
            return $value;
        }

        return null === $value ? null : (string) $value;
    }
}
