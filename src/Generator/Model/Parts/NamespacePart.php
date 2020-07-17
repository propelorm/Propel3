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
 * Trait NamespacePart
 *
 * @author Thomas Gossmann
 * @author Cristiano Cinotti
 */
trait NamespacePart
{
    use SuperordinatePart;

    protected Text $namespace;
    protected Text $name;

    /**
     * @param string|Text $name
     */
    public function setName($name): void
    {
        $name = new Text($name);

        if ($name->contains('\\')) {
            $this->namespace = $name->substring(0, $name->lastIndexOf('\\'));
            $this->name = $name->substring($name->lastIndexOf('\\') + 1);
        } else {
            $this->name = $name;
        }
    }

    /**
     * Sets the namespace
     *
     * @param string|Text $namespace
     */
    public function setNamespace($namespace): void
    {
        $namespace = new Text($namespace);
        $this->namespace = $namespace->trimEnd('\\');
    }

    /**
     * Returns the namespace
     *
     * @return Text
     */
    public function getNamespace(): Text
    {
        $namespace = $this->namespace ?? $this->namespace = new Text();

        if ($namespace->isEmpty() && $this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getNamespace')) {
            $namespace = $this->getSuperordinate()->getNamespace();
        }

        return $namespace;
    }

    /**
     * Returns the class name with namespace.
     *
     * @return Text
     */
    public function getFullName(): Text
    {
        if (!isset($this->namespace) || $this->namespace->isEmpty()) {
            return $this->name;
        }

        return $this->namespace->ensureEnd('\\')->append($this->name);
    }

    public function getName(): Text
    {
        return $this->name ?? $this->name = new Text();
    }
}
