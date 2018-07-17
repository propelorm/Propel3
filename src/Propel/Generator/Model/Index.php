<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model;

use phootwork\collection\ArrayList;
use phootwork\collection\Set;
use Propel\Generator\Model\Parts\EntityPart;
use Propel\Generator\Model\Parts\FieldsPart;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\SuperordinatePart;

/**
 * Information about indices of a entity.
 *
 * @author Jason van Zyl <vanzyl@apache.org>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Index
{
    use NamePart, EntityPart, FieldsPart, SuperordinatePart;

    /**
     * @var bool
     */
    protected $autoNaming = false;

    /**
     * Creates a new Index instance.
     *
     * @param string $name Name of the index
     */
    public function __construct($name = null)
    {
        $this->fields     = new Set();

        if (null !== $name) {
            $this->setName($name);
        }
    }

    /**
     * @inheritdoc
     *
     * @return Entity
     */
    public function getSuperordinate(): Entity
    {
        return $this->getEntity();
    }

    /**
     * Returns the uniqueness of this index.
     *
     * @return boolean
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * Returns the index name.
     *
     * @return string
     */
    public function getName(): string
    {
        $this->doNaming();

        if ($this->entity && $database = $this->entity->getDatabase()) {
            return substr($this->name, 0, $database->getMaxFieldNameLength());
        }

        return $this->name;
    }

    protected function doNaming()
    {
        if (!$this->name || $this->autoNaming) {
            $newName = sprintf('%s_', $this instanceof Unique ? 'u' : 'i');

            if (!$this->fields->isEmpty()) {
                $hash = new ArrayList();
                $this->fields->each(function ($element) use ($hash) {
                    $hash[0] .= $element->getName() . ', ';
                    $hash[1] .= $element->getSize() . ', ';
                });
                $hash->map(function ($element) {
                    $element = substr($element, 0, -2);
                });

                $newName .= substr(md5(strtolower(implode(':', $hash->toArray()))), 0, 6);
            } else {
                $newName .= 'no_fields';
            }

            if ($this->entity) {
                $newName = $this->getEntity()->getName() . '_' . $newName;
            }

            $this->name = $newName;
            $this->autoNaming = true;
        }
    }

    //Used only in SqlDefaultPlatform:730
//    public function getFQName()
//    {
//        $entity = $this->getEntity();
//        if ($entity->getDatabase()
//            && ($entity->getSchema() || $entity->getDatabase()->getSchema())
//            && $entity->getDatabase()->getPlatform()
//            && $entity->getDatabase()->getPlatform()->supportsSchemas()
//        ) {
//            return ($entity->getSchema() ?: $entity->getDatabase()->getSchema()) . '.' . $this->getName();
//        }
//
//        return $this->getName();
//    }

    /**
     * Returns whether or not this index has a given field at a given position.
     *
     * @param  integer $pos             Position in the field list
     * @param  string  $name            Field name
     * @param  integer $size            Optional size check
     * @param  boolean $caseInsensitive Whether or not the comparison is case insensitive (false by default)
     * @return boolean
     */
    public function hasFieldAtPosition($pos, $name, $size = null, $caseInsensitive = false)
    {
        if (!isset($this->fields->toArray()[$pos])) {
            return false;
        }

        if (!$this->hasField($name)) {
            return false;
        }

        if ($this->getField($name, $caseInsensitive)->getSize() != $size) {
            return false;
        }

        return true;
    }
}
