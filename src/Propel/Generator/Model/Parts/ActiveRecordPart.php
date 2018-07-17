<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model\Parts;

/**
 * Trait ActiveRecordPart
 *
 * @author Thomas Gossmann
 */
trait ActiveRecordPart
{
    use SuperordinatePart;

    /**
     * @var bool|null
     */
    private $activeRecord;


    /**
     * @param bool $activeRecord
     * @return $this
     */
    public function setActiveRecord(bool $activeRecord): object
    {
        $this->activeRecord = $activeRecord;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActiveRecord(): bool
    {
        if (null !== $this->activeRecord) {
            return $this->activeRecord;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'isActiveRecord')) {
            return $this->getSuperordinate()->isActiveRecord();
        }

        return $this->activeRecord;
    }

    /**
     * @return bool|null
     */
    public function getActiveRecord(): bool
    {
        return $this->activeRecord;
    }
}
