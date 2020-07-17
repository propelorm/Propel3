<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use phootwork\collection\Map;
use Propel\Generator\Model\Field;

/**
 * Service class for comparing Field objects.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class FieldComparator
{
    /**
     * Compute and return the difference between two column objects
     *
     * @param  Field             $fromField
     * @param  Field             $toField
     * @return FieldDiff|boolean return false if the two columns are similar
     */
    public static function computeDiff(Field $fromField, Field $toField): ?FieldDiff
    {
        $changedProperties = self::compareFields($fromField, $toField);
        if (!$changedProperties->isEmpty()) {
            $platform = $fromField->getPlatform() ?? $toField->getPlatform();
            if (null !== $platform) {
                if ($platform->getFieldDDL($fromField) == $platform->getFieldDDl($toField)) {
                    return null;
                }
            }

            $columnDiff = new FieldDiff($fromField, $toField);
            $columnDiff->setChangedProperties($changedProperties);

            return $columnDiff;
        }

        return null;
    }

    /**
     * @param Field $fromField
     * @param Field $toField
     *
     * @return Map
     */
    public static function compareFields(Field $fromField, Field $toField): Map
    {
        $changedProperties = new Map();

        // compare column types
        $fromDomain = $fromField->getDomain();
        $toDomain = $toField->getDomain();

        if ($fromDomain->getScale() !== $toDomain->getScale()) {
            $changedProperties->set('scale', [$fromDomain->getScale(), $toDomain->getScale()]);
        }
        if ($fromDomain->getSize() !== $toDomain->getSize()) {
            $changedProperties->set('size', [$fromDomain->getSize(), $toDomain->getSize()]);
        }

        if (strtoupper($fromDomain->getSqlType() ?? '') !== strtoupper($toDomain->getSqlType() ?? '')) {
            $changedProperties->set('sqlType', [$fromDomain->getSqlType(), $toDomain->getSqlType()]);

            if ($fromDomain->getType() !== $toDomain->getType()) {
                $changedProperties->set('type', [$fromDomain->getType(), $toDomain->getType()]);
            }
        }

        if ($fromField->isNotNull() !== $toField->isNotNull()) {
            $changedProperties->set('notNull', [$fromField->isNotNull(), $toField->isNotNull()]);
        }

        // compare column default value
        $fromDefaultValue = $fromField->getDefaultValue();
        $toDefaultValue = $toField->getDefaultValue();
        if ($fromDefaultValue && !$toDefaultValue) {
            $changedProperties->set('defaultValueType', [$fromDefaultValue->getType(), null]);
            $changedProperties->set('defaultValueValue', [$fromDefaultValue->getValue(), null]);
        } elseif (!$fromDefaultValue && $toDefaultValue) {
            $changedProperties->set('defaultValueType', [null, $toDefaultValue->getType()]);
            $changedProperties->set('defaultValueValue', [null, $toDefaultValue->getValue()]);
        } elseif ($fromDefaultValue && $toDefaultValue) {
            if (!$fromDefaultValue->equals($toDefaultValue)) {
                if ($fromDefaultValue->getType() !== $toDefaultValue->getType()) {
                    $changedProperties->set('defaultValueType', [$fromDefaultValue->getType(), $toDefaultValue->getType()]);
                }
                if ($fromDefaultValue->getValue() !== $toDefaultValue->getValue()) {
                    $changedProperties->set('defaultValueValue', [$fromDefaultValue->getValue(), $toDefaultValue->getValue()]);
                }
            }
        }

        if ($fromField->isAutoIncrement() !== $toField->isAutoIncrement()) {
            $changedProperties->set('autoIncrement', [$fromField->isAutoIncrement(), $toField->isAutoIncrement()]);
        }

        return $changedProperties;
    }
}
