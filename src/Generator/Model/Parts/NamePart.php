<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\lang\Text;

/**
 * Trait NamePart
 *
 * @author Thomas Gossmann
 */
trait NamePart
{
    protected Text $name;

    /**
     * Returns the class name without namespace.
     */
    public function getName(): Text
    {
        return $this->name ?? $this->name = new Text();
    }

    /**
     * @param string|Text $name
     */
    public function setName($name): void
    {
        $this->name = new Text($name);
    }
}
