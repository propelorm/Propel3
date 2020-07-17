<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Index;

/**
 * Service class for comparing Entity objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class EntityComparator
{
    /**
     * The table difference.
     */
    protected EntityDiff $tableDiff;

    /**
     * Constructor.
     *
     * @param EntityDiff $tableDiff
     */
    public function __construct(EntityDiff $tableDiff = null)
    {
        $this->tableDiff = $tableDiff ?? new EntityDiff();
    }

    /**
     * Returns the table difference.
     *
     * @return EntityDiff
     */
    public function getEntityDiff(): EntityDiff
    {
        return $this->tableDiff;
    }

    /**
     * Sets the table the comparator starts from.
     *
     * @param Entity $fromEntity
     */
    public function setFromEntity(Entity $fromEntity): void
    {
        $this->tableDiff->setFromEntity($fromEntity);
    }

    /**
     * Returns the table the comparator starts from.
     *
     * @return Entity
     */
    public function getFromEntity(): Entity
    {
        return $this->tableDiff->getFromEntity();
    }

    /**
     * Sets the table the comparator goes to.
     *
     * @param Entity $toEntity
     */
    public function setToEntity(Entity $toEntity): void
    {
        $this->tableDiff->setToEntity($toEntity);
    }

    /**
     * Returns the table the comparator goes to.
     *
     * @return Entity
     */
    public function getToEntity(): Entity
    {
        return $this->tableDiff->getToEntity();
    }

    /**
     * Returns the computed difference between two table objects.
     *
     * @param  Entity             $fromEntity
     * @param  Entity             $toEntity
     * @return EntityDiff
     */
    public static function computeDiff(Entity $fromEntity, Entity $toEntity): ?EntityDiff
    {
        $tc = new self();

        $tc->setFromEntity($fromEntity);
        $tc->setToEntity($toEntity);

        $differences = 0;
        $differences += $tc->compareFields();
        $differences += $tc->comparePrimaryKeys();
        $differences += $tc->compareIndices();
        $differences += $tc->compareRelations();
        return ($differences > 0) ? $tc->getEntityDiff() : null;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the columns of the fromEntity and the toEntity,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function compareFields(): int
    {
        $fromEntityFields = $this->getFromEntity()->getFields();
        $toEntityFields = $this->getToEntity()->getFields();
        $columnDifferences = 0;

        // check for new columns in $toEntity
        foreach ($toEntityFields as $column) {
            if (!$this->getFromEntity()->hasFieldByName($column->getName()->toString())) {
                $this->tableDiff->getAddedFields()->set($column->getName()->toString(), $column);
                $columnDifferences++;
            }
        }

        // check for removed columns in $toEntity
        foreach ($fromEntityFields as $column) {
            if (!$this->getToEntity()->hasFieldByName($column->getName()->toString())) {
                $this->tableDiff->getRemovedFields()->set($column->getName()->toString(), $column);
                $columnDifferences++;
            }
        }

        // check for column differences
        foreach ($fromEntityFields as $fromField) {
            if ($this->getToEntity()->hasFieldByName($fromField->getName()->toString())) {
                $toField = $this->getToEntity()->getFieldByName($fromField->getName()->toString());
                $columnDiff = FieldComparator::computeDiff($fromField, $toField);
                if (null !== $columnDiff) {
                    $this->tableDiff->getModifiedFields()->set($fromField->getName()->toString(), $columnDiff);
                    $columnDifferences++;
                }
            }
        }

        // check for column renamings
        foreach ($this->tableDiff->getAddedFields()->toArray() as $addedFieldName => $addedField) {
            foreach ($this->tableDiff->getRemovedFields() as $removedFieldName => $removedField) {
                if (null === FieldComparator::computeDiff($addedField, $removedField)) {
                    // no difference except the name, that's probably a renaming
                    $this->tableDiff->getRenamedFields()->set($removedFieldName, [$removedField, $addedField]);
                    $this->tableDiff->getAddedFields()->remove($addedFieldName);
                    $this->tableDiff->getRemovedFields()->remove($removedFieldName);
                    $columnDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $columnDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the primary keys of the fromEntity and the toEntity,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function comparePrimaryKeys(): int
    {
        $pkDifferences = 0;
        $fromEntityPk = $this->getFromEntity()->getPrimaryKey();
        $toEntityPk = $this->getToEntity()->getPrimaryKey();

        // check for new pk columns in $toEntity
        foreach ($toEntityPk as $column) {
            if (!$this->getFromEntity()->hasFieldByName($column->getName()->toString()) ||
                !$this->getFromEntity()->getFieldByName($column->getName()->toString())->isPrimaryKey()) {
                $this->tableDiff->getAddedPkFields()->set($column->getName(), $column);
                $pkDifferences++;
            }
        }

        // check for removed pk columns in $toEntity
        foreach ($fromEntityPk as $column) {
            if (!$this->getToEntity()->hasFieldByName($column->getName()->toString()) ||
                !$this->getToEntity()->getFieldByName($column->getName()->toString())->isPrimaryKey()) {
                $this->tableDiff->getRemovedPkFields()->set($column->getName(), $column);
                $pkDifferences++;
            }
        }

        // check for column renamings
        foreach ($this->tableDiff->getAddedPkFields()->toArray() as $addedFieldName => $addedField) {
            foreach ($this->tableDiff->getRemovedPkFields() as $removedFieldName => $removedField) {
                if (null === FieldComparator::computeDiff($addedField, $removedField)) {
                    // no difference except the name, that's probably a renaming
                    $this->tableDiff->getRenamedPkFields()->set($removedFieldName, [$removedField, $addedField]);
                    $this->tableDiff->getAddedPkFields()->remove($addedFieldName);
                    $this->tableDiff->getRemovedPkFields()->remove($removedFieldName);
                    $pkDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $pkDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the indices and unique indices of the fromEntity and the toEntity,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function compareIndices(): int
    {
        $indexDifferences = 0;
        $fromEntityIndices = array_merge($this->getFromEntity()->getIndices()->toArray(), $this->getFromEntity()->getUnices()->toArray());
        $toEntityIndices = array_merge($this->getToEntity()->getIndices()->toArray(), $this->getToEntity()->getUnices()->toArray());

        /** @var  Index $fromEntityIndex */
        foreach ($fromEntityIndices as $fromEntityIndexPos => $fromEntityIndex) {
            /** @var  Index $toEntityIndex */
            foreach ($toEntityIndices as $toEntityIndexPos => $toEntityIndex) {
                if ($fromEntityIndex->getName()->compare($toEntityIndex->getName()) === 0) {
                    if (false === IndexComparator::computeDiff($fromEntityIndex, $toEntityIndex)) {
                        //no changes
                        unset($fromEntityIndices[$fromEntityIndexPos]);
                        unset($toEntityIndices[$toEntityIndexPos]);
                    } else {
                        // same name, but different columns
                        $this->tableDiff->getModifiedIndices()->set($fromEntityIndex->getName(), [$fromEntityIndex, $toEntityIndex]);
                        unset($fromEntityIndices[$fromEntityIndexPos]);
                        unset($toEntityIndices[$toEntityIndexPos]);
                        $indexDifferences++;
                    }
                }
            }
        }

        foreach ($fromEntityIndices as $fromEntityIndex) {
            $this->tableDiff->getRemovedIndices()->set($fromEntityIndex->getName(), $fromEntityIndex);
            $indexDifferences++;
        }

        foreach ($toEntityIndices as $toEntityIndex) {
            $this->tableDiff->getAddedIndices()->set($toEntityIndex->getName(), $toEntityIndex);
            $indexDifferences++;
        }

        return $indexDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the foreign keys of the fromEntity and the toEntity,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function compareRelations(): int
    {
        $fkDifferences = 0;
        $fromEntityFks = $this->getFromEntity()->getRelations();
        $toEntityFks = $this->getToEntity()->getRelations();

        foreach ($fromEntityFks as $fromEntityFk) {
            foreach ($toEntityFks as $toEntityFk) {
                if ($fromEntityFk->getName()->compare($toEntityFk->getName()) === 0) {
                    if (false === RelationComparator::computeDiff($fromEntityFk, $toEntityFk)) {
                        $fromEntityFks->remove($fromEntityFk);
                        $toEntityFks->remove($toEntityFk);
                    } else {
                        // same name, but different columns
                        $this->tableDiff->getModifiedFks()->set($fromEntityFk->getName(), [$fromEntityFk, $toEntityFk]);
                        $fromEntityFks->remove($fromEntityFk);
                        $toEntityFks->remove($toEntityFk);
                        $fkDifferences++;
                    }
                }
            }
        }

        foreach ($fromEntityFks as $fromEntityFk) {
            if (!$fromEntityFk->isSkipSql() && !$toEntityFks->contains($fromEntityFk)) {
                $this->tableDiff->getRemovedFks()->set($fromEntityFk->getName(), $fromEntityFk);
                $fkDifferences++;
            }
        }

        foreach ($toEntityFks as $toEntityFk) {
            if (!$toEntityFk->isSkipSql() && !$fromEntityFks->contains($toEntityFk)) {
                $this->tableDiff->getAddedFks()->set($toEntityFk->getName(), $toEntityFk);
                $fkDifferences++;
            }
        }

        return $fkDifferences;
    }
}
