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
 * Class DoubleType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class DoubleType extends AbstractType
{
    /**
     * @param $value
     *
     * @return float
     */
    public function convertToPHPValue($value): double
    {
        return (double) $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     *
     * @return float
     */
    public function databaseToProperty($value, FieldMap $fieldMap): double
    {
        return (double) $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return float
     */
    public function propertyToDatabase($value, FieldMap $fieldMap): float
    {
        return (double) $value;
    }
}
