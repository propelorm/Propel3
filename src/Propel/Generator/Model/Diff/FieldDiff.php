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

use phootwork\collection\Map;
use Propel\Generator\Model\Field;

/**
 * Value object for storing Field object diffs.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class FieldDiff
{
    /**
     * A Map of modified properties.
     *
     * @var Map
     */
    protected $changedProperties;

    /**
     * The original column definition.
     *
     * @var Field
     */
    protected $fromField;

    /**
     * The modified column definition.
     *
     * @var Field
     */
    protected $toField;

    /**
     * Constructor.
     *
     * @param Field $fromField The original column
     * @param Field $toField   The modified column
     */
    public function __construct(Field $fromField = null, Field $toField = null)
    {
        if (null !== $fromField) {
            $this->setFromField($fromField);
        }

        if (null !== $toField) {
            $this->setToField($toField);
        }

        $this->changedProperties = new Map();
    }

    /**
     * Sets for the changed properties.
     *
     * @param Map $properties
     */
    public function setChangedProperties(Map $properties): void
    {
        $this->changedProperties->clear();
        $this->changedProperties->setAll($properties);
    }

    /**
     * Returns the changed properties.
     *
     * @return Map
     */
    public function getChangedProperties(): Map
    {
        return $this->changedProperties;
    }

    /**
     * Sets the fromField property.
     *
     * @param Field $fromField
     */
    public function setFromField(Field $fromField): void
    {
        $this->fromField = $fromField;
    }

    /**
     * Returns the fromField property.
     *
     * @return Field
     */
    public function getFromField(): Field
    {
        return $this->fromField;
    }

    /**
     * Sets the toField property.
     *
     * @param Field $toField
     */
    public function setToField(Field $toField): void
    {
        $this->toField = $toField;
    }

    /**
     * Returns the toField property.
     *
     * @return Field
     */
    public function getToField(): Field
    {
        return $this->toField;
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return FieldDiff
     */
    public function getReverseDiff(): FieldDiff
    {
        $diff = new self();

        // columns
        $diff->setFromField($this->toField);
        $diff->setToField($this->fromField);

        // properties
        $changedProperties = [];
        foreach ($this->changedProperties as $name => $propertyChange) {
            $changedProperties[$name] = array_reverse($propertyChange);
        }
        $diff->setChangedProperties(new Map($changedProperties));

        return $diff;
    }

    /**
     * Returns the string representation of the difference.
     *
     * @return string
     */
    public function __toString()
    {
        $ret = '';
        $ret .= sprintf("      %s:\n", $this->fromField->getFullyQualifiedName());
        $ret .= "        modifiedProperties:\n";
        foreach ($this->changedProperties as $key => $value) {
            $ret .= sprintf("          %s: %s\n", $key, json_encode($value));
        }

        return $ret;
    }
}
