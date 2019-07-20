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

use Propel\Generator\Model\Entity;

/**
 * Trait EntityPart
 *
 * @author Cristiano Cinotti
 */
trait EntityPart
{
    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @param Entity $entity
     *
     * @return $this
     */
    public function setEntity(?Entity $entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return Entity
     */
    public function getEntity(): ?Entity
    {
        return $this->entity;
    }
}
