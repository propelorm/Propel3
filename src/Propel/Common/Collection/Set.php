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

namespace Propel\Common\Collection;

use phootwork\collection\Set as BaseSet;

class Set extends BaseSet
{
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
}
