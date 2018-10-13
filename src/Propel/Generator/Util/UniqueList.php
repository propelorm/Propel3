<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

declare(strict_types=1);

namespace Propel\Generator\Util;

use phootwork\collection\ArrayList;

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
    public function add($element, $index = null) {
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
