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

use Propel\Generator\Model\Schema;

/**
 * Trait SchemaPart
 *
 * @author Thomas Gossman
 */
trait SchemaPart
{
    /** @var Schema */
    protected $schema;

    /**
     * @param Schema $schema
     *
     * @return mixed
     */
    abstract protected function registerSchema(Schema $schema);

    /**
     * @param Schema $schema
     *
     * @return mixed
     */
    abstract protected function unregisterSchema(Schema $schema);

    /**
     * Sets the parent schema (will make this an external schema)
     *
     * @param Schema $schema
     * @return $this
     */
    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
        if ($schema) {
            $this->registerSchema($schema);
        } else {
            $this->unregisterSchema($schema);
        }

        return $this;
    }

    /**
     * Returns the parent schema
     *
     * @return Schema
     */
    public function getSchema(): ?Schema
    {
        return $this->schema;
    }
}
