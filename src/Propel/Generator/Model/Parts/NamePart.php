<?php
namespace Propel\Generator\Model\Parts;

trait NamePart
{
    protected $name;

    /**
     * Returns the class name without namespace.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }
}
