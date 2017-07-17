<?php
namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Model;

trait GeneratorPart {

    use SuperordinatePart;

    /**
     * The accessor visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $accessorVisibility = null;

    /**
     * The mutator visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $mutatorVisibility = null;

    /**
     * Sets the visibility for mutators
     *
     * @param string $visibility
     * @return $this
     */
    public function setMutatorVisibility(string $visibility)
    {
        $this->mutatorVisibility = $visibility;
        return $this;
    }

    /**
     * Returns the visibility for mutators
     *
     * @return string
     */
    public function getMutatorVisibility(): string
    {
        if (null !== $this->mutatorVisibility) {
            return $this->mutatorVisibility;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getMutatorVisibility')) {
            return $this->getSuperordinate()->getMutatorVisibility();
        }

        return Model::DEFAULT_ACCESSOR_ACCESSIBILITY;
    }

    /**
     * Sets the visibility for accessors
     *
     * @param string $visibility
     * @return $this
     */
    public function setAccessorVisibility(string $visibility)
    {
        $this->accessorVisibility = $visibility;
        return $this;
    }

    /**
     * Returns the visibility for accessors
     * @return string
     */
    public function getAccessorVisibility(): string
    {
        if (null !== $this->accessorVisibility) {
            return $this->accessorVisibility;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getAccessorVisibility')) {
            return $this->getSuperordinate()->getAccessorVisibility();
        }

        return Model::DEFAULT_MUTATOR_ACCESSIBILITY;
    }
}

