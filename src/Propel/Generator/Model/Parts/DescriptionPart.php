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
 * Trait DescriptionPart
 *
 * @author Cristiano Cinotti
 */
trait DescriptionPart
{
    /**
     * @var string
     */
    protected $description;

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Returns whether or not the entity has a description.
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return !empty($this->description);
    }
}
