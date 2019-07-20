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

/**
 * Class UniqueList
 *
 * ArrayList with unique values
 */
class UniqueList extends ArrayList
{
    /**
     * Adds an element to that list
     *
     * @param mixed $element
     * @param int $index
     * @return $this
     */
    public function add($element, $index = null)
    {
        $this->checkClass($element);

        if (!in_array($element, $this->collection, true)) {
            if ($index === null) {
                $this->collection[$this->size()] = $element;
            } else {
                array_splice($this->collection, $index, 0, $element);
            }
        }

        return $this;
    }
}
