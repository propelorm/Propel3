<?php
namespace Propel\Generator\Model\Parts;

trait SchemaNamePart
{
    protected $schemaName;

    /**
     * Returns the schema name.
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }

    /**
     * Sets the schema name.
     *
     * @param string $schemaName
     */
    public function setSchemaName(string $schemaName)
    {
        if ($schemaName === $this->schemaName) {
            return;
        }

        $this->schemaName = $schemaName;
//         if ($schemaName && !$this->package && $this->getBuildProperty('schemaAutoPackage')) {
//             $this->package = $schemaName;
//             $this->packageOverridden = true;
//         }

//         if ($schemaName && !$this->namespace && $this->getBuildProperty('schemaAutoNamespace')) {
//             $this->namespace = $schemaName;
//         }
    }
}
