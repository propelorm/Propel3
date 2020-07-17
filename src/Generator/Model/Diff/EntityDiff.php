<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use phootwork\collection\Map;
use phootwork\json\Json;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Entity;

/**
 * Value object for storing Entity object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class EntityDiff
{
    /**
     * The first Entity object.
     *
     * @var Entity
     */
    protected Entity $fromEntity;

    /**
     * The second Entity object.
     *
     * @var Entity
     */
    protected Entity $toEntity;

    /**
     * The list of added fields.
     *
     * Map format:
     *  key   => The name of added field
     *  value => The added Field object
     *
     * @var Map
     */
    protected Map $addedFields;

    /**
     * The list of removed fields.
     *
     * Map format:
     *  key  => The name of the removed field
     *  value => The removed Field object
     *
     * @var Map
     */
    protected Map $removedFields;

    /**
     * The list of modified fields.
     *
     * Map format:
     *  key   => The name of modified field
     *  value => The FieldDiff object, mapping the modification
     *
     * @var Map
     */
    protected Map $modifiedFields;

    /**
     * The list of renamed fields.
     *
     * Map format:
     *  key   => The name of the field
     *  value => Array of Field objects [$fromField, $toField]
     *
     * @var Map
     */
    protected Map $renamedFields;

    /**
     * The list of added primary key columns.
     *
     * @var Map
     */
    protected Map $addedPkFields;

    /**
     * The list of removed primary key columns.
     *
     * @var Map
     */
    protected Map $removedPkFields;

    /**
     * The list of renamed primary key columns.
     *
     * @var Map
     */
    protected Map $renamedPkFields;

    /**
     * The list of added indices.
     *
     * Map format:
     *  key   => The name of the index
     *  value => The Index object
     *
     * @var Map
     */
    protected Map $addedIndices;

    /**
     * The list of removed indices.
     *
     * Map format:
     *  key   => The name of the index
     *  value => The Index object
     *
     * @var Map
     */
    protected Map $removedIndices;

    /**
     * The list of modified indices.
     *
     * Map format:
     *  key   => The name of the modified index
     *  value => array of Index objects [$fromIndex, $toIndex]
     *
     * @var Map
     */
    protected Map $modifiedIndices;

    /**
     * The list of added relations.
     *
     * Map format:
     *  key   => The name of added relation
     *  value => The Relation object
     *
     * @var Map
     */
    protected Map $addedFks;

    /**
     * The list of removed foreign keys.
     *
     * Map format:
     *  key   => The name of added relation
     *  value => The Relation object
     *
     * @var Map
     */
    protected Map $removedFks;

    /**
     * The list of modified columns.
     *
     * Map format:
     *  key   => The name of the modified relation
     *  value => array of Relation objects [$fromRelation, $toRelation]
     *
     * @var Map
     */
    protected Map $modifiedFks;

    /**
     * Constructor.
     *
     * @param Entity $fromEntity The first table
     * @param Entity $toEntity   The second table
     */
    public function __construct(Entity $fromEntity = null, Entity $toEntity = null)
    {
        if (null !== $fromEntity) {
            $this->setFromEntity($fromEntity);
        }

        if (null !== $toEntity) {
            $this->setToEntity($toEntity);
        }

        $this->addedFields     = new Map();
        $this->removedFields   = new Map();
        $this->modifiedFields  = new Map();
        $this->renamedFields   = new Map();
        $this->addedPkFields   = new Map();
        $this->removedPkFields = new Map();
        $this->renamedPkFields = new Map();
        $this->addedIndices     = new Map();
        $this->modifiedIndices  = new Map();
        $this->removedIndices   = new Map();
        $this->addedFks         = new Map();
        $this->modifiedFks      = new Map();
        $this->removedFks       = new Map();
    }

    /**
     * Sets the fromEntity property.
     *
     * @param Entity $fromEntity
     */
    public function setFromEntity(Entity $fromEntity): void
    {
        $this->fromEntity = $fromEntity;
    }

    /**
     * Returns the fromEntity property.
     *
     * @return Entity
     */
    public function getFromEntity(): Entity
    {
        return $this->fromEntity;
    }

    /**
     * Sets the toEntity property.
     *
     * @param Entity $toEntity
     */
    public function setToEntity(Entity $toEntity): void
    {
        $this->toEntity = $toEntity;
    }

    /**
     * Returns the toEntity property.
     *
     * @return Entity
     */
    public function getToEntity(): Entity
    {
        return $this->toEntity;
    }

    /**
     * Sets the added columns.
     *
     * @param Map $columns
     */
    public function setAddedFields(Map $columns): void
    {
        $this->addedFields->clear();
        $this->addedFields->setAll($columns);
    }

    /**
     * Returns the list of added columns
     *
     * @return Map
     */
    public function getAddedFields(): Map
    {
        return $this->addedFields;
    }

    /**
     * Setter for the removedFields property
     *
     * @param Map $removedFields
     */
    public function setRemovedFields(Map $removedFields): void
    {
        $this->removedFields->clear();
        $this->removedFields->setAll($removedFields);
    }

    /**
     * Getter for the removedFields property.
     *
     * @return Map
     */
    public function getRemovedFields(): Map
    {
        return $this->removedFields;
    }

    /**
     * Sets the list of modified columns.
     *
     * @param Map $modifiedFields An associative array of FieldDiff objects
     */
    public function setModifiedFields(Map $modifiedFields): void
    {
        $this->modifiedFields->clear();
        $this->modifiedFields->setAll($modifiedFields);
    }

    /**
     * Getter for the modifiedFields property
     *
     * @return Map
     */
    public function getModifiedFields(): Map
    {
        return $this->modifiedFields;
    }

    /**
     * Sets the list of renamed columns.
     *
     * @param Map $renamedFields
     */
    public function setRenamedFields(Map $renamedFields): void
    {
        $this->renamedFields->clear();
        $this->renamedFields->setAll($renamedFields);
    }

    /**
     * Getter for the renamedFields property
     *
     * @return Map
     */
    public function getRenamedFields(): Map
    {
        return $this->renamedFields;
    }

    /**
     * Sets the list of added primary key columns.
     *
     * @param Map $addedPkFields
     */
    public function setAddedPkFields(Map $addedPkFields): void
    {
        $this->addedPkFields->clear();
        $this->addedPkFields->setAll($addedPkFields);
    }

    /**
     * Getter for the addedPkFields property
     *
     * @return Map
     */
    public function getAddedPkFields(): Map
    {
        return $this->addedPkFields;
    }

    /**
     * Sets the list of removed primary key columns.
     *
     * @param Map $removedPkFields
     */
    public function setRemovedPkFields(Map $removedPkFields): void
    {
        $this->removedPkFields->clear();
        $this->removedPkFields->setAll($removedPkFields);
    }

    /**
     * Getter for the removedPkFields property
     *
     * @return Map
     */
    public function getRemovedPkFields(): Map
    {
        return $this->removedPkFields;
    }

    /**
     * Sets the list of all renamed primary key columns.
     *
     * @param Map $renamedPkFields
     */
    public function setRenamedPkFields(Map $renamedPkFields): void
    {
        $this->renamedPkFields->clear();
        $this->renamedPkFields->setAll($renamedPkFields);
    }

    /**
     * Getter for the renamedPkFields property
     *
     * @return Map
     */
    public function getRenamedPkFields(): Map
    {
        return $this->renamedPkFields;
    }

    /**
     * Whether the primary key was modified
     *
     * @return boolean
     */
    public function hasModifiedPk(): bool
    {
        return
            !$this->renamedPkFields->isEmpty() ||
            !$this->removedPkFields->isEmpty() ||
            !$this->addedPkFields->isEmpty()
        ;
    }

    /**
     * Sets the list of new added indices.
     *
     * @param Map $addedIndices
     */
    public function setAddedIndices(Map $addedIndices): void
    {
        $this->addedIndices->clear();
        $this->addedIndices->setAll($addedIndices);
    }

    /**
     * Getter for the addedIndices property
     *
     * @return Map
     */
    public function getAddedIndices(): Map
    {
        return $this->addedIndices;
    }

    /**
     * Set the list of removed indices.
     *
     * @param Map $removedIndices
     */
    public function setRemovedIndices(Map $removedIndices): void
    {
       $this->removedIndices->clear();
       $this->removedIndices->setAll($removedIndices);
    }

    /**
     * Getter for the removedIndices property
     *
     * @return Map
     */
    public function getRemovedIndices(): Map
    {
        return $this->removedIndices;
    }

    /**
     * Sets the list of modified indices.
     *
     * Array must be [ [ Index $fromIndex, Index $toIndex ], [ ... ] ]
     *
     * @param Map $modifiedIndices A set of modified indices
     */
    public function setModifiedIndices(Map $modifiedIndices): void
    {
        $this->modifiedIndices->clear();
        $this->modifiedIndices->setAll($modifiedIndices);
    }

    /**
     * Getter for the modifiedIndices property
     *
     * @return Map
     */
    public function getModifiedIndices(): Map
    {
        return $this->modifiedIndices;
    }

    /**
     * Sets the list of added foreign keys.
     *
     * @param Map $addedFks
     */
    public function setAddedFks(Map $addedFks): void
    {
        $this->addedFks->clear();
        $this->addedFks->setAll($addedFks);
    }

    /**
     * Getter for the addedFks property
     *
     * @return Map
     */
    public function getAddedFks(): Map
    {
        return $this->addedFks;
    }

    /**
     * Sets the list of removed foreign keys.
     *
     * @param Map $removedFks
     */
    public function setRemovedFks(Map $removedFks): void
    {
        $this->removedFks->clear();
        $this->removedFks->setAll($removedFks);
    }

    /**
     * Returns the list of removed foreign keys.
     *
     * @return Map
     */
    public function getRemovedFks(): Map
    {
        return $this->removedFks;
    }

    /**
     * Sets the list of modified foreign keys.
     *
     * Array must be [ [ Relation $fromFk, Relation $toFk ], [ ... ] ]
     *
     * @param Map $modifiedFks
     */
    public function setModifiedFks(Map $modifiedFks): void
    {
        $this->modifiedFks->clear();
        $this->modifiedFks->setAll($modifiedFks);
    }

    /**
     * Returns the list of modified foreign keys.
     *
     * @return Map
     */
    public function getModifiedFks(): Map
    {
        return $this->modifiedFks;
    }

    /**
     * Returns whether or not there are
     * some modified foreign keys.
     *
     * @return boolean
     */
    public function hasModifiedFks(): bool
    {
        return !$this->modifiedFks->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some modified indices.
     *
     * @return boolean
     */
    public function hasModifiedIndices(): bool
    {
        return !$this->modifiedIndices->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some modified columns.
     *
     * @return boolean
     */
    public function hasModifiedFields(): bool
    {
        return !$this->modifiedFields->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed foreign keys.
     *
     * @return boolean
     */
    public function hasRemovedFks(): bool
    {
        return !$this->removedFks->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed indices.
     *
     * @return boolean
     */
    public function hasRemovedIndices(): bool
    {
        return !$this->removedIndices->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some renamed columns.
     *
     * @return boolean
     */
    public function hasRenamedFields(): bool
    {
        return !$this->renamedFields->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed columns.
     *
     * @return boolean
     */
    public function hasRemovedFields(): bool
    {
        return !$this->removedFields->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added columns.
     *
     * @return boolean
     */
    public function hasAddedFields(): bool
    {
        return !$this->addedFields->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added indices.
     *
     * @return boolean
     */
    public function hasAddedIndices(): bool
    {
        return !$this->addedIndices->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added foreign keys.
     *
     * @return boolean
     */
    public function hasAddedFks(): bool
    {
        return !$this->addedFks->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added primary key columns.
     *
     * @return boolean
     */
    public function hasAddedPkFields(): bool
    {
        return !$this->addedPkFields->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed primary key columns.
     *
     * @return boolean
     */
    public function hasRemovedPkFields(): bool
    {
        return !$this->removedPkFields->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some renamed primary key columns.
     *
     * @return boolean
     */
    public function hasRenamedPkFields(): bool
    {
        return !$this->renamedPkFields->isEmpty();
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return EntityDiff
     */
    public function getReverseDiff(): EntityDiff
    {
        $diff = new self();

        // tables
        $diff->setFromEntity($this->toEntity);
        $diff->setToEntity($this->fromEntity);

        // columns
        if ($this->hasAddedFields()) {
            $diff->setRemovedFields($this->addedFields);
        }

        if ($this->hasRemovedFields()) {
            $diff->setAddedFields($this->removedFields);
        }

        if ($this->hasRenamedFields()) {
            $renamedFields = [];
            foreach ($this->renamedFields as $columnRenaming) {
                $renamedFields[$columnRenaming[1]->getName()->toString()] = array_reverse($columnRenaming);
            }
            $diff->setRenamedFields(new Map($renamedFields));
        }

        if ($this->hasModifiedFields()) {
            $columnDiffs = [];
            foreach ($this->modifiedFields as $name => $columnDiff) {
                $columnDiffs[$name] = $columnDiff->getReverseDiff();
            }
            $diff->setModifiedFields(new Map($columnDiffs));
        }

        // pks
        if ($this->hasRemovedPkFields()) {
            $diff->setAddedPkFields($this->removedPkFields);
        }

        if ($this->hasAddedPkFields()) {
            $diff->setRemovedPkFields($this->addedPkFields);
        }

        if ($this->hasRenamedPkFields()) {
            $renamedPkFields = [];
            foreach ($this->renamedPkFields as $columnRenaming) {
                $renamedPkFields[$columnRenaming[1]->getName()->toString()] = array_reverse($columnRenaming);
            }
            $diff->setRenamedPkFields(new Map($renamedPkFields));
        }

        // indices
        if ($this->hasRemovedIndices()) {
            $diff->setAddedIndices($this->removedIndices);
        }

        if ($this->hasAddedIndices()) {
            $diff->setRemovedIndices($this->addedIndices);
        }

        if ($this->hasModifiedIndices()) {
            $indexDiffs = [];
            foreach ($this->modifiedIndices as $name => $indexDiff) {
                $indexDiffs[$name] = array_reverse($indexDiff);
            }
            $diff->setModifiedIndices(new Map($indexDiffs));
        }

        // fks
        if ($this->hasAddedFks()) {
            $diff->setRemovedFks($this->addedFks);
        }

        if ($this->hasRemovedFks()) {
            $diff->setAddedFks($this->removedFks);
        }

        if ($this->hasModifiedFks()) {
            $fkDiffs = [];
            foreach ($this->modifiedFks as $name => $fkDiff) {
                $fkDiffs[$name] = array_reverse($fkDiff);
            }
            $diff->setModifiedFks(new Map($fkDiffs));
        }

        return $diff;
    }

    /**
     * Clones the current diff object.
     *
     */
    public function __clone()
    {
        if ($this->fromEntity) {
            $this->fromEntity = clone $this->fromEntity;
        }
        if ($this->toEntity) {
            $this->toEntity = clone $this->toEntity;
        }
    }

    /**
     * Returns the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        $ret = '';
        $ret .= sprintf("  %s:\n", $this->fromEntity->getName());
        if ($addedFields = $this->getAddedFields()) {
            $ret .= "    addedFields:\n";
            foreach ($addedFields as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($removedFields = $this->getRemovedFields()) {
            $ret .= "    removedFields:\n";
            foreach ($removedFields as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($modifiedFields = $this->getModifiedFields()) {
            $ret .= "    modifiedFields:\n";
            foreach ($modifiedFields as $colDiff) {
                $ret .= (string) $colDiff;
            }
        }
        if ($renamedFields = $this->getRenamedFields()) {
            $ret .= "    renamedFields:\n";
            foreach ($renamedFields as $columnRenaming) {
                [$fromField, $toField] = $columnRenaming;
                $ret .= sprintf("      %s: %s\n", $fromField->getName(), $toField->getName());
            }
        }
        if ($addedIndices = $this->getAddedIndices()) {
            $ret .= "    addedIndices:\n";
            foreach ($addedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($removedIndices = $this->getRemovedIndices()) {
            $ret .= "    removedIndices:\n";
            foreach ($removedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($modifiedIndices = $this->getModifiedIndices()) {
            $ret .= "    modifiedIndices:\n";
            foreach ($modifiedIndices as $indexName => $indexDiff) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($addedFks = $this->getAddedFks()) {
            $ret .= "    addedFks:\n";
            foreach ($addedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($removedFks = $this->getRemovedFks()) {
            $ret .= "    removedFks:\n";
            foreach ($removedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($modifiedFks = $this->getModifiedFks()) {
            $ret .= "    modifiedFks:\n";
            foreach ($modifiedFks as $fkName => $fkFromTo) {
                $ret .= sprintf("      %s:\n", $fkName);
                /**
                 * @var Relation $fromFk
                 * @var Relation $toFk
                 */
                [$fromFk, $toFk] = $fkFromTo;
                $fromLocalFields = Json::encode($fromFk->getLocalFields()->toArray());
                $toLocalFields = Json::encode($toFk->getLocalFields()->toArray());

                if ($fromLocalFields != $toLocalFields) {
                    $ret .= sprintf("          localFields: from %s to %s\n", $fromLocalFields, $toLocalFields);
                }
                $fromForeignFields = Json::encode($fromFk->getForeignFields()->toArray());
                $toForeignFields = Json::encode($toFk->getForeignFields()->toArray());
                if ($fromForeignFields != $toForeignFields) {
                    $ret .= sprintf("          foreignFields: from %s to %s\n", $fromForeignFields, $toForeignFields);
                }
                if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) != $toFk->normalizeFKey($toFk->getOnUpdate())) {
                    $ret .= sprintf("          onUpdate: from %s to %s\n", $fromFk->getOnUpdate(), $toFk->getOnUpdate());
                }
                if ($fromFk->normalizeFKey($fromFk->getOnDelete()) != $toFk->normalizeFKey($toFk->getOnDelete())) {
                    $ret .= sprintf("          onDelete: from %s to %s\n", $fromFk->getOnDelete(), $toFk->getOnDelete());
                }
            }
        }

        return $ret;
    }
}
