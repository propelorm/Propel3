<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Relation;

/**
 * Service class for comparing Relation objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 */
class RelationComparator
{
    /**
     * Compute the difference between two Foreign key objects
     *
     * @param Relation $fromFk
     * @param Relation $toFk
     *
     * @return boolean false if the two fks are similar, true if they have differences
     */
    public static function computeDiff(Relation $fromFk, Relation $toFk): bool
    {
        if ($fromFk->getEntityName()->compare($toFk->getEntityName()) !== 0) {
            return true;
        }

        if ($fromFk->getForeignEntityName() !== $toFk->getForeignEntityName()) {
            return true;
        }

        // compare columns
        $fromFkLocalFields = $fromFk->getLocalFields()->sort()->map("strtolower");
        $toFkLocalFields = $toFk->getLocalFields()->sort()->map("strtolower");

        if ($fromFkLocalFields->toArray() !== $toFkLocalFields->toArray()) {
            return true;
        }

        $fromFkForeignFields = $fromFk->getForeignFields()->sort()->map("strtolower");
        $toFkForeignFields = $toFk->getForeignFields()->sort()->map("strtolower");

        if ($fromFkForeignFields->toArray() !== $toFkForeignFields->toArray()) {
            return true;
        }

        // compare on
        if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) !== $toFk->normalizeFKey($toFk->getOnUpdate())) {
            return true;
        }
        if ($fromFk->normalizeFKey($fromFk->getOnDelete()) !== $toFk->normalizeFKey($toFk->getOnDelete())) {
            return true;
        }

        // compare skipSql
        return $fromFk->isSkipSql() !== $toFk->isSkipSql();
    }
}
