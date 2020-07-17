<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

/**
 * Trait ActiveRecordPart
 *
 * @author Thomas Gossmann
 */
trait ActiveRecordPart
{
    use SuperordinatePart;

    private bool $activeRecord;


    public function setActiveRecord(bool $activeRecord): void
    {
        $this->activeRecord = $activeRecord;
    }

    /**
     * @return bool
     */
    public function isActiveRecord(): bool
    {
        if (isset($this->activeRecord)) {
            return $this->activeRecord;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getActiveRecord')) {
            return $this->getSuperordinate()->getActiveRecord();
        }

        return false;
    }

    /**
     * @deprecated use isActiveRecord
     * @return bool
     */
    public function getActiveRecord(): bool
    {
        return $this->isActiveRecord();
    }
}
