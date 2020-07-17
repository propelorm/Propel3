<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Index;

/**
 * Service class for comparing Index objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class IndexComparator
{
    /**
     * Computes the difference between two index objects.
     *
     * @param  Index   $fromIndex
     * @param  Index   $toIndex
     * @return boolean
     */
    public static function computeDiff(Index $fromIndex, Index $toIndex): bool
    {
        // Check for removed index columns in $toIndex
        $fromIndexFields = $fromIndex->getFields();
        $i = 0;
        foreach ($fromIndexFields as $indexField) {
            if (!$toIndex->hasFieldAtPosition($i, (string) $indexField->getName(), $indexField->getSize())) {
                return true;
            }
            $i++;
        }

        // Check for new index columns in $toIndex
        $toIndexFields = $toIndex->getFields();
        $i = 0;
        foreach ($toIndexFields as $indexField) {
            if (!$fromIndex->hasFieldAtPosition($i, (string) $indexField->getName(), $indexField->getSize())) {
                return true;
            }
            $i++;
        }

        // Check for difference in unicity
        return $fromIndex->isUnique() !== $toIndex->isUnique();
    }
}
