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

use phootwork\collection\ArrayList as BaseArrayList;

class ArrayList extends BaseArrayList
{
    use CollectionTrait;

    public function add($element, $index = null)
    {
        $this->checkClass($element);
        parent::add($element, $index);
    }
}
