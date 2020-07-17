<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use phootwork\collection\Set;
use Propel\Generator\Model\Database;

/**
 * Service class for comparing Database objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseComparator
{
    protected DatabaseDiff $databaseDiff;
    protected Database $fromDatabase;
    protected Database $toDatabase;

    /**
     * Whether we should detect renamings and track it via `addRenamedEntity` at the
     * DatabaseDiff object.
     *
     * @var bool
     */
    protected bool $withRenaming = false;
    protected bool $removeEntity = true;
    protected Set $excludedEntities;

    public function __construct(DatabaseDiff $databaseDiff = null)
    {
        $this->databaseDiff = $databaseDiff ?? new DatabaseDiff();
        $this->excludedEntities = new Set();
    }

    public function getDatabaseDiff(): DatabaseDiff
    {
        return $this->databaseDiff;
    }

    /**
     * Sets the fromDatabase property.
     *
     * @param Database $fromDatabase
     */
    public function setFromDatabase(Database $fromDatabase): void
    {
        $this->fromDatabase = $fromDatabase;
    }

    /**
     * Sets the toDatabase property.
     *
     * @param Database $toDatabase
     */
    public function setToDatabase(Database $toDatabase): void
    {
        $this->toDatabase = $toDatabase;
    }

    /**
     * Set true to handle removed tables or false to ignore them
     *
     * @param boolean $removeEntity
     */
    public function setRemoveEntity(bool $removeEntity): void
    {
        $this->removeEntity = $removeEntity;
    }

    /**
     * @return boolean
     */
    public function getRemoveEntity(): bool
    {
        return $this->removeEntity;
    }

    /**
     * Set the list of tables excluded from the comparison
     *
     * @param array $excludedEntities set the list of table name
     */
    public function addExcludedEntities(?array $excludedEntities = null): void
    {
        $excludedEntities = $excludedEntities ?? [];
        $this->excludedEntities->add(...$excludedEntities);
    }

    /**
     * Returns the list of tables excluded from the comparison
     *
     * @return Set
     */
    public function getExcludedEntities(): Set
    {
        return $this->excludedEntities;
    }

    /**
     * Returns the computed difference between two database objects.
     *
     * @param  Database $fromDatabase
     * @param  Database $toDatabase
     * @param  bool $withRenaming
     * @param  bool $removeEntity
     * @param  array $excludedEntities Entities to exclude from the difference computation
     *
     * @return DatabaseDiff
     */
    public static function computeDiff(
        Database $fromDatabase,
        Database $toDatabase,
        bool $withRenaming = false,
        bool $removeEntity = true,
        ?array $excludedEntities = null): ?DatabaseDiff
    {
        $databaseComparator = new self();
        $databaseComparator->setFromDatabase($fromDatabase);
        $databaseComparator->setToDatabase($toDatabase);
        $databaseComparator->setWithRenaming($withRenaming);
        $databaseComparator->setRemoveEntity($removeEntity);
        $databaseComparator->addExcludedEntities($excludedEntities);

        $platform = $toDatabase->getPlatform() ?? $fromDatabase->getPlatform();

        if ($platform) {
            foreach ($fromDatabase->getEntities() as $table) {
                $platform->normalizeEntity($table);
            }
            foreach ($toDatabase->getEntities() as $table) {
                $platform->normalizeEntity($table);
            }
        }

        $differences = 0;
        $differences += $databaseComparator->compareEntities();

        return ($differences > 0) ? $databaseComparator->getDatabaseDiff() : null;
    }

    /**
     * @param boolean $withRenaming
     */
    public function setWithRenaming(bool $withRenaming): void
    {
        $this->withRenaming = $withRenaming;
    }

    /**
     * @return boolean
     */
    public function getWithRenaming(): bool
    {
        return $this->withRenaming;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the tables of the fromDatabase and the toDatabase, and modifies
     * the inner databaseDiff if necessary.
     *
     * @return integer
     */
    public function compareEntities(): int
    {
        $fromDatabaseEntities = $this->fromDatabase->getEntities();
        $toDatabaseEntities = $this->toDatabase->getEntities();
        $databaseDifferences = 0;

        // check for new tables in $toDatabase
        foreach ($toDatabaseEntities as $table) {
            if ($this->excludedEntities->contains($table->getName()->toString())) {
                continue;
            }
            if (!$this->fromDatabase->hasEntityByName($table->getName()->toString()) && !$table->isSkipSql()) {
                $this->databaseDiff->getAddedEntities()->set($table->getName()->toString(), $table);
                $databaseDifferences++;
            }
        }

        // check for removed tables in $toDatabase
        if ($this->getRemoveEntity()) {
            foreach ($fromDatabaseEntities as $table) {
                if ($this->excludedEntities->contains($table->getName()->toString())) {
                    continue;
                }
                if (!$this->toDatabase->hasEntityByName($table->getName()->toString()) && !$table->isSkipSql()) {
                    $this->databaseDiff->getRemovedEntities()->set($table->getName()->toString(), $table);
                    $databaseDifferences++;
                }
            }
        }

        // check for table differences
        foreach ($fromDatabaseEntities as $fromEntity) {
            if ($this->excludedEntities->contains($fromEntity->getName()->toString())) {
                continue;
            }
            if ($this->toDatabase->hasEntityByName($fromEntity->getName()->toString())) {
                $toEntity = $this->toDatabase->getEntityByName($fromEntity->getName()->toString());
                $databaseDiff = EntityComparator::computeDiff($fromEntity, $toEntity);
                if (null !== $databaseDiff) {
                    $this->databaseDiff->getModifiedEntities()->set($fromEntity->getName()->toString(), $databaseDiff);
                    $databaseDifferences++;
                }
            }
        }

        // check for table renamings
        foreach ($this->databaseDiff->getAddedEntities()->toArray() as $addedEntityName => $addedEntity) {
            foreach ($this->databaseDiff->getRemovedEntities()->toArray() as $removedEntityName => $removedEntity) {
                if (null === EntityComparator::computeDiff($addedEntity, $removedEntity)) {
                    // no difference except the name, that's probably a renaming
                    if ($this->getWithRenaming()) {
                        $this->databaseDiff->getRenamedEntities()->set($removedEntityName, $addedEntityName);
                        $this->databaseDiff->getAddedEntities()->remove($addedEntityName);
                        $this->databaseDiff->getRemovedEntities()->remove($removedEntityName);
                        $databaseDifferences--;
                    } else {
                        $this->databaseDiff->getPossibleRenamedEntities()->set($removedEntityName, $addedEntityName);
                    }
                    // skip to the next added table
                    break;
                }
            }
        }

        return $databaseDifferences;
    }
}
