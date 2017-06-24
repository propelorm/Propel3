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
 * Class IntegerType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class IntegerType extends AbstractType
{
    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     *
     * @return int|null
     */
    public function databaseToProperty($value, FieldMap $fieldMap):? int
    {
        if (null === $value) {
            return null;
        }

        return (int) $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return int|mixed|null
     */
    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_array($value)) {
            return $value;
        }

        if (null === $value) {
            return null;
        }

        return (int) $value;
    }
}
