<?php
namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Schema;

trait SchemaPart
{

    /** @var Schema */
    protected $schema;

    abstract protected function registerSchema(Schema $schema);
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
