<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Entity;

/**
 * Trait EntityPart
 *
 * @author Cristiano Cinotti
 */
trait EntityPart
{
    protected ?Entity $entity;

    public function setEntity(?Entity $entity): void
    {
        $this->entity = $entity;
    }

    public function getEntity(): ?Entity
    {
        return $this->entity ?? null;
    }
}
