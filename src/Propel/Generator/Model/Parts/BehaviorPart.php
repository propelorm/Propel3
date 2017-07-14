<?php
namespace Propel\Generator\Model\Parts;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Behavior;
use phootwork\collection\Map;

/**
 * BehaviorableTrait use it on every model that can hold behaviors
 *
 */
trait BehaviorPart
{
    /** @var Map */
    protected $behaviors;

//     /** @var BehaviorManager */
//     private $behaviorManager;

//     /**
//      * @return GeneratorConfigInterface
//      */
//     abstract protected function getGeneratorConfig();

    protected function initBehaviors()
    {
        $this->behaviors = new Map();
    }

//     /**
//      * Returns the behavior locator.
//      *
//      * @return BehaviorLocator
//      */
//     private function getBehaviorManager()
//     {
//         if (null === $this->behaviorManager) {
//             $config = $this->getGeneratorConfig();
//             if (null !== $config) {
//                 $this->behaviorManager = $config->getBehaviorManager();
//                 if (null === $this->behaviorManager) {
//                     $this->behaviorManager = new BehaviorManager();
//                 }
//             } else {
//                 $this->behaviorManager = new BehaviorManager();
//             }
//         }

//         return $this->behaviorManager;
//     }

    /**
     * Adds a new Behavior
     *
     * @param Behavior $behavior
     * @throws BuildException when the added behavior is not an instance of \Propel\Generator\Model\Behavior
     * @return $this
     */
    public function addBehavior(Behavior $behavior)
    {
        // the new behavior is already registered
        if ($this->hasBehavior($behavior->getId()) && $behavior->allowMultiple()) {

            // the user probably just forgot to specify the "id" attribute
            if ($behavior->getId() === $behavior->getName()) {
                throw new BuildException(sprintf('Behavior "%s" is already registered. Specify a different ID attribute to register the same behavior several times.',
                    $behavior->getName()));
            }

            // or he copy-pasted it and forgot to update it.
            else {
                throw new BuildException(sprintf('A behavior with ID "%s" is already registered.', $behavior->getId()));
            }
        }

        $this->registerBehavior($behavior);
        $this->behaviors->set($behavior->getId(),  $behavior);

        return $this;

//         $locator = $this->getBehaviorLocator();
//         $class = $locator->getBehavior($bdata['name']);
//         $behavior = new $class();
//         if (!($behavior instanceof Behavior)) {
//             throw new BuildException(sprintf('Behavior [%s: %s] not instance of %s',
//                 $bdata['name'], $class, '\Propel\Generator\Model\Behavior'));
//         }
//         $behavior->loadMapping($bdata);

//         return $this->addBehavior($behavior);
    }

    abstract protected function registerBehavior(Behavior $behavior);

    /**
     * Removes the behavior
     * @param Behavior $behavior
     * @return $this
     */
    public function removeBehavior(Behavior $behavior) {
        $this->unregisterBehavior($behavior);
        $this->behaviors->remove($behavior->getId());

        return $this;
    }

    abstract protected function unregisterBehavior(Behavior $behavior);

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
    public function hasBehavior($id): bool
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
        if ($this->hasBehavior($id)) {
            return $this->behaviors->get($id);
        }

        return null;
    }
}
