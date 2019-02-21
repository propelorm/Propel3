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

use phootwork\collection\Map as BaseMap;

class Map extends BaseMap
{
    use CollectionTrait;

    public function set($key, $element)
    {
        $this->checkClass($element);
        parent::set($key, $element);
    }

    public function get($key, $default = null)
    {
        if (null !== $default) {
            $this->checkClass($default);
        }

        return parent::get($key, $default);
    }
}
