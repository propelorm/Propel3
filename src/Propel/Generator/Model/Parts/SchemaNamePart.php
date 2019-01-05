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

/**
 * Trait SchemaNamePart
 *
 * @author Thomas Gossmann
 */
trait SchemaNamePart
{
    /**
     * @var string
     */
    protected $schemaName;

    /**
     * Returns the schema name.
     *
     * @return string|null
     */
    public function getSchemaName(): ?string
    {
        if (null === $this->schemaName) {
            if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getSchemaName')) {
                return $this->getSuperordinate()->getSchemaName();
            }
        }

        return $this->schemaName;
    }

    /**
     * Sets the schema name.
     *
     * @param string $schemaName
     */
    public function setSchemaName(string $schemaName): void
    {
        if ($schemaName === $this->schemaName) {
            return;
        }

        $this->schemaName = $schemaName;
    }
}
