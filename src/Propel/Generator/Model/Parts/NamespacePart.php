<?php
namespace Propel\Generator\Model\Parts;

trait NamespacePart 
{
    protected $name;
    protected $namespace;

    /**
     * Returns the class name without namespace.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @param string $name
     */
    public function setName($name)
    {
        if (false !== strpos($name, '\\')) {
            $namespace = explode('\\', trim($name, '\\'));
            $this->name = array_pop($namespace);
            $this->namespace = implode('\\', $namespace);
        } else {
            $this->name = $name;
        }
    }
}

