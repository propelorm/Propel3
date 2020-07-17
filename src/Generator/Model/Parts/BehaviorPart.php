<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\collection\Map;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Behavior;

trait BehaviorPart
{
    protected Map $behaviors;

    /**
     * @param Behavior $behavior
     */
    abstract protected function registerBehavior(Behavior $behavior): void;

    /**
     * @param Behavior $behavior
     */
    abstract protected function unregisterBehavior(Behavior $behavior): void;

    protected function initBehaviors()
    {
        $this->behaviors = new Map();
    }

    /**
     * Adds a new Behavior
     *
     * @param Behavior $behavior
     * @throws BuildException when the added behavior is not an instance of \Propel\Generator\Model\Behavior
     */
    public function addBehavior(Behavior $behavior): void
    {
        // the new behavior is already registered
        if ($this->hasBehavior($behavior->getId()) && $behavior->allowMultiple()) {

            // the user probably just forgot to specify the "id" attribute
            if ($behavior->getId() === $behavior->getName()) {
                throw new BuildException(sprintf(
                    'Behavior "%s" is already registered. Specify a different ID attribute to register the same behavior several times.',
                    $behavior->getName()
                ));
            }

            // or he copy-pasted it and forgot to update it.
            else {
                throw new BuildException(sprintf('A behavior with ID "%s" is already registered.', $behavior->getId()));
            }
        }

        $this->registerBehavior($behavior);
        $this->behaviors->set($behavior->getId(), $behavior);
    }

    /**
     * Removes the behavior
     * @param Behavior $behavior
     */
    public function removeBehavior(Behavior $behavior): void
    {
        $this->unregisterBehavior($behavior);
        $this->behaviors->remove($behavior->getId());
    }

    /**
     * Returns the list of behaviors.
     *
     * @return Behavior[]
     */
    public function getBehaviors(): array
    {
        return $this->behaviors->toArray();
    }

    /**
     * check if the given behavior exists
     *
     * @param string $id the behavior id
     * @return bool True if the behavior exists
     */
    public function hasBehavior(string $id): bool
    {
        return $this->behaviors->has($id);
    }

    /**
     * Get behavior by id
     *
     * @param string $id the behavior id
     * @return Behavior|null a behavior object or null if the behavior doesn't exist
     */
    public function getBehavior($id): ?Behavior
    {
        return $this->behaviors->get($id);
    }
}
