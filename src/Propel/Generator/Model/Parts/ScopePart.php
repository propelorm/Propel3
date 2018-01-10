<?php
namespace Propel\Generator\Model\Parts;

trait ScopePart
{
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
        $scope = $this->scope;

        if (null === $scope && $this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getScope')) {
            $scope = $this->getSuperordinate()->getScope();
        }

        return null === $scope ? '' : $scope;
    }
}
