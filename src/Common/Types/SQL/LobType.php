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
 * Class LobType
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class LobType extends AbstractType
{
//    public function convertToPHPValue($value, FieldMap $fieldMap)
//    {
//        if (is_resource($value)) {
//            return $value;
//        }
//
//        return $value;
//    }
//
//    public function getPHPType(Field $field)
//    {
//        return 'resource';
//    }

    /**
     * {@inheritdoc}
     *
     * @param $value
     * @param FieldMap $fieldMap
     *
     * @return bool|string
     */
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        if (is_resource($value)) {
            rewind($value);
            $value = stream_get_contents($value);
        } else {
            $value = (string) $value;
        }

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
}
