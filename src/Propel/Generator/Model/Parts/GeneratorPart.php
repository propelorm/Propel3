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

use Propel\Generator\Model\Model;

/**
 * Trait GeneratorPart
 *
 * @author Thomas Gossmann
 */
trait GeneratorPart
{
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
        if (!in_array($visibility, [Model::VISIBILITY_PUBLIC, Model::VISIBILITY_PRIVATE, Model::VISIBILITY_PROTECTED])) {
            $visibility = Model::VISIBILITY_PUBLIC;
        }

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

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getMutatorVisibility')) {var_dump('eccolo');
            return $this->getSuperordinate()->getMutatorVisibility();
        }

        return Model::DEFAULT_MUTATOR_ACCESSIBILITY;
    }

    /**
     * Sets the visibility for accessors
     *
     * @param string $visibility
     * @return $this
     */
    public function setAccessorVisibility(string $visibility)
    {
        if (!in_array($visibility, [Model::VISIBILITY_PUBLIC, Model::VISIBILITY_PRIVATE, Model::VISIBILITY_PROTECTED])) {
            $visibility = Model::VISIBILITY_PUBLIC;
        }

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

        return Model::DEFAULT_ACCESSOR_ACCESSIBILITY;
    }
}
