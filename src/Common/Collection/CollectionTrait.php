<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Common\Collection;

use Propel\Common\Collection\Exception\CollectionException;

/**
 * Trait CollectionTrait
 *
 * Add some useful behaviors to phootwork/collection classes.
 *
 * @package Propel\Common\Collection
 * @author Cristiano Cinotti
 */
trait CollectionTrait
{
    /**
     *The class of the objects in this collection.
     *
     * @var string
     */
    private $class;

    /**
     * CollectionTrait constructor.
     *
     * Passing a classname parameter, trigger some checks to ensure that the collection
     * is only made of objects of that class.
     *
     * @param array|\Iterator $collection
     * @param string|null $className
     */
    public function __construct($collection = [], string $className = null)
    {
        if (null !== $className) {
            $this->class = $className;
            foreach ($collection as $object) {
                $this->checkClass($object);
            }
        }

        parent::__construct($collection);
    }

    /**
     * If it's an object collection, clone this objects, too.
     */
    public function __clone()
    {
        $clonedCollection = [];
        foreach ($this->collection as $key => $element) {
            if (is_object($element)) {
                $clonedCollection[$key] = clone $element;
                continue;
            }
            $clonedCollection[$key] = $element;
        }
        $this->collection = $clonedCollection;
    }

    public function hasClass(): bool
    {
        return (null !== $this->class);
    }

    public function checkClass($object)
    {
        if ($this->hasClass()) {
            if (!($object instanceof $this->class)) {
                throw new CollectionException("The given objects should be an instance of {$this->class}");
            }
        }
    }
}
