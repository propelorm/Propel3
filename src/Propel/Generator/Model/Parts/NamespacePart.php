<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

/**
 * Trait NamespacePart
 *
 * @author Thomas Gossmann
 */
trait NamespacePart
{
    use NamePart;
    use SuperordinatePart;

    /** @var string */
    protected $namespace;

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): object
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
    public function setNamespace(?string $namespace): object
    {
        if (null !== $namespace) {
            $namespace = rtrim($namespace, '\\');
        }
        $this->namespace = $namespace;

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
