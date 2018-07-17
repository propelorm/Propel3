<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model\Parts;

/**
 * Trait NamePart
 *
 * @author Thomas Gossmann
 */
trait NamePart
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * Returns the class name without namespace.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }
}
