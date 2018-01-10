<?php
namespace Propel\Generator\Model\Parts;

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
    public function setActiveRecord(bool $activeRecord)
    {
        $this->activeRecord = $activeRecord;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActiveRecord()
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
    public function getActiveRecord()
    {
        return $this->activeRecord;
    }
}
