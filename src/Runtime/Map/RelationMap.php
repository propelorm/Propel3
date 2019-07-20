<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Map;

/**
 * RelationMap is used to model a database relationship.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Francois Zaninotto
 */
class RelationMap
{
    // types
    const MANY_TO_ONE       = 1;

    const ONE_TO_MANY       = 2;

    const ONE_TO_ONE        = 3;

    const MANY_TO_MANY      = 4;

    // representations
    const LOCAL_TO_FOREIGN  = 0;

    const LEFT_TO_RIGHT     = 1;

    protected $name;

    protected $refName;

    protected $type;

    protected $localEntity;

    protected $foreignEntity;

    /**
     * @var EntityMap
     */
    protected $middleEntity;

    /**
     * @var string
     */
    protected $middleEntityTableName;

    /**
     * @var bool
     */
    protected $implementationDetail = false;

    protected $fieldMappingIncomingName;
    protected $fieldMappingIncoming;

    protected $fieldMappingOutgoing;

    protected $fieldMappingPrimaryKeys;

    /**
     * @var boolean
     */
    protected $polymorphic = false;

    /**
     * @var FieldMap[]
     */
    protected $localFields = [];

    /**
     * @var FieldMap[]
     */
    protected $foreignFields = [];

    /**
     * @var string
     */
    protected $onUpdate;

    /**
     * @var string
     */
    protected $onDelete;

    /**
     * Whether this relation is a referrer.
     *
     * @var bool
     */
    protected $referrer = false;

    /**
     * Constructor.
     *
     * @param string $name Name of the relation.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this relation.
     *
     * @return string The name of the relation.
     */
    public function getName()
    {
        return $this->name;
    }

    public function setRefName($refName)
    {
        $this->refName = $refName;
    }

    /**
     * @return EntityMap
     */
    public function getMiddleEntity()
    {
        return $this->middleEntity;
    }

    /**
     * @param EntityMap $middleEntity
     */
    public function setMiddleEntity(EntityMap $middleEntity)
    {
        $this->middleEntity = $middleEntity;
    }

    /**
     * @return mixed
     */
    public function getMiddleEntityTableName()
    {
        return $this->middleEntityTableName;
    }

    /**
     * @param mixed $middleEntityTableName
     */
    public function setMiddleEntityTableName($middleEntityTableName)
    {
        $this->middleEntityTableName = $middleEntityTableName;
    }

    /**
     * @return boolean
     */
    public function isImplementationDetail()
    {
        return $this->implementationDetail;
    }

    /**
     * @param boolean $implementationDetail
     */
    public function setImplementationDetail($implementationDetail)
    {
        $this->implementationDetail = $implementationDetail;
    }

    /**
     * @return string[]
     */
    public function getFieldMappingIncoming()
    {
        return $this->fieldMappingIncoming;
    }

    /**
     * @param string[] $fieldMappingIncoming
     */
    public function setFieldMappingIncoming($fieldMappingIncoming)
    {
        $this->fieldMappingIncoming = $fieldMappingIncoming;
    }

    /**
     * @return mixed
     */
    public function getFieldMappingOutgoing()
    {
        return $this->fieldMappingOutgoing;
    }

    /**
     * @param mixed $fieldMappingOutgoing
     */
    public function setFieldMappingOutgoing($fieldMappingOutgoing)
    {
        $this->fieldMappingOutgoing = $fieldMappingOutgoing;
    }

    /**
     * @return mixed
     */
    public function getFieldMappingPrimaryKeys()
    {
        return $this->fieldMappingPrimaryKeys;
    }

    /**
     * @param mixed $fieldMappingPrimaryKeys
     */
    public function setFieldMappingPrimaryKeys($fieldMappingPrimaryKeys)
    {
        $this->fieldMappingPrimaryKeys = $fieldMappingPrimaryKeys;
    }

    /**
     * @return mixed
     */
    public function getFieldMappingIncomingName()
    {
        return $this->fieldMappingIncomingName;
    }

    /**
     * @param mixed $fieldMappingIncomingName
     */
    public function setFieldMappingIncomingName($fieldMappingIncomingName)
    {
        $this->fieldMappingIncomingName = $fieldMappingIncomingName;
    }

    /**
     * @return boolean
     */
    public function isPolymorphic()
    {
        return $this->polymorphic;
    }

    /**
     * @param boolean $polymorphic
     */
    public function setPolymorphic($polymorphic)
    {
        $this->polymorphic = $polymorphic;
    }

    /**
     * @return boolean
     */
    public function isReferrer()
    {
        return $this->referrer;
    }

    /**
     * @return bool
     */
    public function isOwnerSide()
    {
        if ($this->isManyToMany()) {
            throw new \LogicException('In MANY_TO_MANY relations there is no such thing as owner side.');
        }

        return !$this->referrer;
    }

    /**
     * @param boolean $referrer
     */
    public function setReferrer($referrer)
    {
        $this->referrer = $referrer;
    }

    /**
     * Get the plural name of this relation.
     *
     * @return string The plural name of the relation.
     */
    public function getRefName()
    {
        return $this->refName;
    }

    /**
     * Set the type
     *
     * @param int $type The relation type (either self::MANY_TO_ONE, self::ONE_TO_MANY, or self::ONE_TO_ONE)
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isOutgoingRelation()
    {
        return static::MANY_TO_ONE === $this->type;
    }

    /**
     * Get the type
     *
     * @return int the relation type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the local entity
     *
     * @param \Propel\Runtime\Map\EntityMap $entity The local entity for this relationship
     */
    public function setLocalEntity(EntityMap $entity)
    {
        $this->localEntity = $entity;
    }

    /**
     * Get the local entity
     *
     * @return \Propel\Runtime\Map\EntityMap The local entity for this relationship
     */
    public function getLocalEntity()
    {
        return $this->localEntity;
    }

    /**
     * Set the foreign entity
     *
     * @param \Propel\Runtime\Map\EntityMap $entity The foreign entity for this relationship
     */
    public function setForeignEntity($entity)
    {
        $this->foreignEntity = $entity;
    }

    /**
     * Get the foreign entity
     *
     * @return \Propel\Runtime\Map\EntityMap The foreign entity for this relationship
     */
    public function getForeignEntity()
    {
        return $this->foreignEntity;
    }

    /**
     * Get the left entity of the relation
     *
     * @return \Propel\Runtime\Map\EntityMap The left entity for this relationship
     */
    public function getLeftEntity()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getLocalEntity() : $this->getForeignEntity();
    }

    /**
     * Get the right entity of the relation
     *
     * @return \Propel\Runtime\Map\EntityMap The right entity for this relationship
     */
    public function getRightEntity()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getForeignEntity() : $this->getLocalEntity();
    }

    /**
     * Add a field mapping
     *
     * @param \Propel\Runtime\Map\FieldMap $local   The local field
     * @param \Propel\Runtime\Map\FieldMap $foreign The foreign field
     */
    public function addFieldMapping(FieldMap $local, FieldMap $foreign)
    {
        $this->localFields[] = $local;
        $this->foreignFields[] = $foreign;
    }

    /**
     * Get an associative array mapping local field names to foreign field names
     * The arrangement of the returned array depends on the $direction parameter:
     *  - If the value is RelationMap::LOCAL_TO_FOREIGN, then the returned array is local => foreign
     *  - If the value is RelationMap::LEFT_TO_RIGHT, then the returned array is left => right
     *
     * @param  int   $direction How the associative array must return fields
     * @return array Associative array (local => foreign) of fully qualified field names
     */
    public function getFieldMappings($direction = RelationMap::LOCAL_TO_FOREIGN)
    {
        $h = [];
        if (RelationMap::LEFT_TO_RIGHT === $direction
            && RelationMap::MANY_TO_ONE === $this->getType()) {
            $direction = RelationMap::LOCAL_TO_FOREIGN;
        }

        for ($i = 0, $size = count($this->localFields); $i < $size; $i++) {
            if (RelationMap::LOCAL_TO_FOREIGN === $direction) {
                $h[$this->localFields[$i]->getFullyQualifiedName()] = $this->foreignFields[$i]->getFullyQualifiedName();
            } else {
                $h[$this->foreignFields[$i]->getFullyQualifiedName()] = $this->localFields[$i]->getFullyQualifiedName();
            }
        }

        return $h;
    }

    /**
     * Get an associative array mapping local field names to foreign field names
     * The arrangement of the returned array depends on the $direction parameter:
     *  - If the value is RelationMap::LOCAL_TO_FOREIGN, then the returned array is local => foreign
     *  - If the value is RelationMap::LEFT_TO_RIGHT, then the returned array is left => right
     *
     * @param  int   $direction How the associative array must return fields
     * @return string[] Associative array (local => foreign) of field names
     */
    public function getFieldNameObjectMappings($direction = RelationMap::LOCAL_TO_FOREIGN)
    {
        $h = [];
        if (RelationMap::LEFT_TO_RIGHT === $direction
            && RelationMap::MANY_TO_ONE === $this->getType()) {
            $direction = RelationMap::LOCAL_TO_FOREIGN;
        }

        for ($i = 0, $size = count($this->localFields); $i < $size; $i++) {
            if (RelationMap::LOCAL_TO_FOREIGN === $direction) {
                $h[$this->localFields[$i]->getName()] = $this->foreignFields[$i]->getName();
            } else {
                $h[$this->foreignFields[$i]->getName()] = $this->localFields[$i]->getName();
            }
        }

        return $h;
    }

    /**
     * When one local field is required we assume that the object
     * should be removed from the database when its incoming reference is null.
     *
     * @return boolean
     */
    public function isOrphanRemoval()
    {
        foreach ($this->getLocalFields() as $field) {
            if ($field->isNotNull()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isOnDeleteCascade()
    {
        return $this->getOnDelete() === 'cascade';
    }

    /**
     * @return bool
     */
    public function isOnDeleteRestrict()
    {
        return $this->getOnDelete() === 'restrict';
    }

    /**
     * @return bool
     */
    public function isOnDeleteSetNull()
    {
        return $this->getOnDelete() === 'setnull';
    }

    /**
     * @return bool
     */
    public function isOnUpdateCascade()
    {
        return $this->getOnUpdate() === 'cascade';
    }

    /**
     * @return bool
     */
    public function isOnUpdateRestrict()
    {
        return $this->getOnUpdate() === 'restrict';
    }

    /**
     * @return bool
     */
    public function isOnUpdateSetNull()
    {
        return $this->getOnUpdate() === 'setnull';
    }

    /**
     * @return bool
     */
    public function isManyToMany()
    {
        return RelationMap::MANY_TO_MANY === $this->getType();
    }

    /**
     * @return bool
     */
    public function isManyToOne()
    {
        return RelationMap::MANY_TO_ONE === $this->getType();
    }

    /**
     * @return bool
     */
    public function isOneToMany()
    {
        return RelationMap::ONE_TO_MANY === $this->getType();
    }

    /**
     * @return bool
     */
    public function isOneToOne()
    {
        return RelationMap::ONE_TO_ONE === $this->getType();
    }

    /**
     * Returns true if the relation has more than one field mapping
     *
     * @return boolean
     */
    public function isComposite()
    {
        return $this->countFieldMappings() > 1;
    }

    /**
     * Return the number of field mappings
     *
     * @return int
     */
    public function countFieldMappings()
    {
        return count($this->localFields);
    }

    /**
     * Get the local fields
     *
     * @return FieldMap[]
     */
    public function getLocalFields()
    {
        return $this->localFields;
    }

    /**
     * Get the foreign fields
     *
     * @return FieldMap[]
     */
    public function getForeignFields()
    {
        return $this->foreignFields;
    }

    /**
     * Get the left fields of the relation
     *
     * @return FieldMap[]
     */
    public function getLeftFields()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getLocalFields() : $this->getForeignFields();
    }

    /**
     * Get the right fields of the relation
     *
     * @return FieldMap[]
     */
    public function getRightFields()
    {
        return RelationMap::MANY_TO_ONE === $this->getType() ? $this->getForeignFields() : $this->getLocalFields();
    }

    /**
     * Set the onUpdate behavior
     *
     * @param string $onUpdate
     */
    public function setOnUpdate($onUpdate)
    {
        $this->onUpdate = strtolower($onUpdate);
    }

    /**
     * Get the onUpdate behavior
     *
     * @return string
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    /**
     * Set the onDelete behavior
     *
     * @param string $onDelete
     */
    public function setOnDelete($onDelete)
    {
        $this->onDelete = strtolower($onDelete);
    }

    /**
     * Get the onDelete behavior
     *
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * Gets the symmetrical relation
     *
     * @return \Propel\Runtime\Map\RelationMap
     */
    public function getSymmetricalRelation()
    {
        $localMapping = [$this->getLeftFields(), $this->getRightFields()];
        foreach ($this->getRightEntity()->getRelations() as $relation) {
            $relationMapping = [$relation->getRightFields(), $relation->getLeftFields()];
            if ($localMapping == $relationMapping) {
                return $relation;
            }
        }
    }
}
