<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Schema;

/**
 * Trait SchemaPart
 *
 * @author Thomas Gossman
 */
trait SchemaPart
{
    protected Schema $schema;

    /**
     * @param Schema $schema
     */
    abstract protected function registerSchema(Schema $schema): void;

    /**
     * Sets the parent schema (will make this an external schema)
     *
     * @param Schema $schema
     */
    public function setSchema(Schema $schema): void
    {
        $this->schema = $schema;
        $this->registerSchema($schema);
    }

    /**
     * Returns the parent schema
     *
     * @return Schema
     */
    public function getSchema(): ?Schema
    {
        return $this->schema ?? null;
    }
}
