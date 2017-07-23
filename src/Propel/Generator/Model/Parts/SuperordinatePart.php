<?php
namespace Propel\Generator\Model\Parts;

trait SuperordinatePart {

    /**
     * Returns the superordinate model if present
     */
    abstract protected function getSuperordinate();
}

