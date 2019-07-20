<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

/**
 * Trait SuperordinatePart
 *
 * @author Thomas Gossmann
 */
trait SuperordinatePart
{
    /**
     * Returns the superordinate model if present
     */
    abstract protected function getSuperordinate();
}
