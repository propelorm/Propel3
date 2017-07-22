<?php
namespace Propel\Generator\Model\Parts;

trait NamespacePart
{
    use NamePart;
    use SuperordinatePart;

    protected $namespace;

    public function setName($name)
    {
        if (false !== strpos($name, '\\')) {
            $namespace = explode('\\', trim($name, '\\'));
            $this->name = array_pop($namespace);
            $this->namespace = implode('\\', $namespace);
        } else {
            $this->name = $name;
        }
        return $this;
    }

    /**
     * Sets the namespace
     *
     * @param string $namespace
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = rtrim($namespace, '\\');

        return $this;
    }

    /**
     * Returns the namespace
     *
     * @return string
     */
    public function getNamespace(): string
    {
        $namespace = $this->namespace;

        if (null === $namespace && $this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getNamespace')) {
            $namespace = $this->getSuperordinate()->getNamespace();
        }

        if (null === $namespace) {
            $namespace = '';
        }

        return $namespace;
    }

    /**
     * Returns the class name with namespace.
     *
     * @return string
     */
    public function getFullName(): string
    {
        $name = $this->getName();
        $namespace = $this->getNamespace();

        if ($namespace) {
            return $namespace . '\\' . $name;
        } else {
            return $name;
        }
    }

}

