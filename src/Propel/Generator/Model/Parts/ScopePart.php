<?php
namespace Propel\Generator\Model\Parts;

trait ScopePart {

    use SuperordinatePart;

    private $scope;

    /**
     * Sets scope
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Returns scope
     *
     * @return string
     */
    public function getScope(): string
    {
        if (null !== $this->scope) {
            return $this->scope;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getScope')) {
            return $this->getSuperordinate()->getScope();
        }
    }
}

