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
 * Trait DescriptionPart
 *
 * @author Cristiano Cinotti
 */
trait DescriptionPart
{
    protected Text $description;

    public function getDescription(): Text
    {
        if (!isset($this->description)) {
            $this->description = new Text();
        }

        return $this->description;
    }

    /**
     * @param string|Text $description
     */
    public function setDescription($description): void
    {
        $this->description = new Text($description);
    }

    /**
     * Returns whether or not the entity has a description.
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return !$this->getDescription()->isEmpty();
    }
}
