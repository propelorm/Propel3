<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\lang\Text;

/**
 * Trait SchemaNamePart
 *
 * @author Thomas Gossmann
 */
trait SchemaNamePart
{
    protected Text $schemaName;

    /**
     * Returns the schema name.
     *
     * @return Text
     */
    public function getSchemaName(): Text
    {
        if (!isset($this->schemaName)) {
            if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getSchemaName')) {
                return $this->getSuperordinate()->getSchemaName();
            }
        }

        return $this->schemaName ?? new Text();
    }

    /**
     * Sets the schema name.
     *
     * @param string|Text $schemaName
     */
    public function setSchemaName($schemaName): void
    {
        $this->schemaName = new Text($schemaName);
    }
}
