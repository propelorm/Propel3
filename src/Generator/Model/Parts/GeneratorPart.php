<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

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
    private string $accessorVisibility = Model::DEFAULT_ACCESSOR_ACCESSIBILITY;

    /**
     * The mutator visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private string $mutatorVisibility = Model::DEFAULT_MUTATOR_ACCESSIBILITY;

    /**
     * Sets the visibility for mutators
     *
     * @param string $visibility
     */
    public function setMutatorVisibility(string $visibility): void
    {
        if (!in_array($visibility, [Model::VISIBILITY_PUBLIC, Model::VISIBILITY_PRIVATE, Model::VISIBILITY_PROTECTED])) {
            $visibility = Model::VISIBILITY_PUBLIC;
        }

        $this->mutatorVisibility = $visibility;
    }

    /**
     * Returns the visibility for mutators
     *
     * @return string
     */
    public function getMutatorVisibility(): string
    {
        if ($this->mutatorVisibility === Model::DEFAULT_MUTATOR_ACCESSIBILITY) {
            if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getMutatorVisibility')) {
                return $this->getSuperordinate()->getMutatorVisibility();
            }
        }

        return $this->mutatorVisibility;
    }

    /**
     * Sets the visibility for accessors
     *
     * @param string $visibility
     */
    public function setAccessorVisibility(string $visibility): void
    {
        if (!in_array($visibility, [Model::VISIBILITY_PUBLIC, Model::VISIBILITY_PRIVATE, Model::VISIBILITY_PROTECTED])) {
            $visibility = Model::VISIBILITY_PUBLIC;
        }

        $this->accessorVisibility = $visibility;
    }

    /**
     * Returns the visibility for accessors
     * @return string
     */
    public function getAccessorVisibility(): string
    {
        if ($this->accessorVisibility === Model::DEFAULT_ACCESSOR_ACCESSIBILITY) {
            if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getAccessorVisibility')) {
                return $this->getSuperordinate()->getAccessorVisibility();
            }
        }

        return $this->accessorVisibility;
    }
}
