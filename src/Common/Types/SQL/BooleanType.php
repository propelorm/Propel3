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
 * Class BooleanType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BooleanType extends AbstractType
{
    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return bool
     */
    public function databaseToProperty($value, FieldMap $fieldMap): bool
    {
        return $value ? true : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param FieldMap $fieldMap
     *
     * @return int
     */
    public function propertyToDatabase($value, FieldMap $fieldMap): int
    {
        return (int) $value;
    }
}
