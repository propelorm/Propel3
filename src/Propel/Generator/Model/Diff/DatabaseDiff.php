<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model\Diff;

use Propel\Common\Collection\Map;
use Propel\Generator\Model\Model;

/**
 * Value object for storing Database object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseDiff
{
    /** @var Map */
    protected $addedEntities;

    /** @var Map */
    protected $removedEntities;

    /** @var Map */
    protected $modifiedEntities;

    /** @var Map  */
    protected $renamedEntities;

    /** @var Map  */
    protected $possibleRenamedEntities;

    public function __construct()
    {
        $this->addedEntities    = new Map();
        $this->removedEntities  = new Map();
        $this->modifiedEntities = new Map();
        $this->renamedEntities  = new Map();
        $this->possibleRenamedEntities  = new Map();
    }

    /**
     * @return Map
     */
    public function getPossibleRenamedEntities(): Map
    {
        return $this->possibleRenamedEntities;
    }

    /**
     * Returns the list of added tables.
     *
     * @return Map
     */
    public function getAddedEntities(): Map
    {
        return $this->addedEntities;
    }

    /**
     * Set the addedEntities collection: all data will be overridden.
     *
     * @param Map $tables
     */
    public function setAddedEntities(Map $tables)
    {
        $this->addedEntities->clear();
        $this->addedEntities->setAll($tables);
    }

    /**
     * Returns the list of removed tables.
     *
     * @return Map
     */
    public function getRemovedEntities(): Map
    {
        return $this->removedEntities;
    }

    /**
     * Set the removedEntities collection: all data will be overridden.
     *
     * @param Map $tables
     */
    public function setRemovedEntities(Map $tables)
    {
        $this->removedEntities->clear();
        $this->removedEntities->setAll($tables);
    }

    /**
     * Returns the modified tables.
     *
     * @return Map
     */
    public function getModifiedEntities(): Map
    {
        return $this->modifiedEntities;
    }

    /**
     * Set the modifiedEntities collection: all data will be overridden.
     *
     * @param Map $tables
     */
    public function setModifiedEntities(Map $tables)
    {
        $this->modifiedEntities->clear();
        $this->modifiedEntities->setAll($tables);
    }

    /**
     * Returns the list of renamed tables.
     *
     * @return Map
     */
    public function getRenamedEntities(): Map
    {
        return $this->renamedEntities;
    }

    /**
     * Set the renamedEntities collection: all data will be overridden.
     *
     * @param Map $table
     */
    public function setRenamedEntities(Map $table)
    {
        $this->renamedEntities->clear();
        $this->renamedEntities->setAll($table);
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return DatabaseDiff
     */
    public function getReverseDiff(): DatabaseDiff
    {
        $diff = new self();
        $diff->setAddedEntities($this->getRemovedEntities());
        // idMethod is not set for tables build from reverse engineering
        // FIXME: this should be handled by reverse classes
        foreach ($diff->getAddedEntities() as $table) {
            if ($table->getIdMethod() == Model::ID_METHOD_NONE) {
                $table->setIdMethod(Model::ID_METHOD_NATIVE);
            }
        }
        $diff->setRemovedEntities($this->getAddedEntities());
        $diff->setRenamedEntities(new Map(array_flip($this->getRenamedEntities()->toArray())));
        $tableDiffs = new Map();
        foreach ($this->getModifiedEntities() as $name => $tableDiff) {
            $tableDiffs->set($name, $tableDiff->getReverseDiff());
        }
        $diff->setModifiedEntities($tableDiffs);

        return $diff;
    }

    /**
     * Returns a description of the database modifications.
     *
     * @return string
     */
    public function getDescription(): string
    {
        $changes = [];
        if ($count = $this->getAddedEntities()->size()) {
            $changes[] = sprintf('%d added tables', $count);
        }
        if ($count = $this->getRemovedEntities()->size()) {
            $changes[] = sprintf('%d removed tables', $count);
        }
        if ($count = $this->getModifiedEntities()->size()) {
            $changes[] = sprintf('%d modified tables', $count);
        }
        if ($count = $this->getRenamedEntities()->size()) {
            $changes[] = sprintf('%d renamed tables', $count);
        }

        return implode(', ', $changes);
    }

    public function __toString()
    {
        $ret = '';
        if ($addedEntities = $this->getAddedEntities()) {
            $ret .= "addedEntities:\n";
            foreach ($addedEntities as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($removedEntities = $this->getRemovedEntities()) {
            $ret .= "removedEntities:\n";
            foreach ($removedEntities as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($modifiedEntities = $this->getModifiedEntities()) {
            $ret .= "modifiedEntities:\n";
            foreach ($modifiedEntities as $tableDiff) {
                $ret .= $tableDiff->__toString();
            }
        }
        if ($renamedEntities = $this->getRenamedEntities()) {
            $ret .= "renamedEntities:\n";
            foreach ($renamedEntities as $fromName => $toName) {
                $ret .= sprintf("  %s: %s\n", $fromName, $toName);
            }
        }

        return $ret;
    }
}
