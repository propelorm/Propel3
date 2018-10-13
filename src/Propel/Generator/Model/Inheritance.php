<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model;

/**
 * A class for information regarding possible objects representing a table.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Inheritance
{
    /** @var string */
    private $key;

    /** @var string */
    private $className;

    /** @var string */
    private $package;

    /** @var string */
    private $ancestor;

    /** @var Field */
    private $field;

    /**
     * Returns a key name.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Sets a key name.
     *
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * Returns the parent field.
     *
     * @return Field
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * Sets the parent field
     *
     * @param Field $field
     */
    public function setField(Field $field): void
    {
        $this->field = $field;
    }

    /**
     * Returns the class name.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Sets the class name.
     *
     * @param string $name
     */
    public function setClassName(string $name): void
    {
        $this->className = $name;
    }

    /**
     * Returns the package.
     *
     * @return string
     */
    public function getPackage(): string
    {
        return $this->package;
    }

    /**
     * Sets the package.
     *
     * @param string $package
     */
    public function setPackage(string $package): void
    {
        $this->package = $package;
    }

    /**
     * Returns the ancestor value.
     *
     * @return string
     */
    public function getAncestor(): string
    {
        return $this->ancestor;
    }

    /**
     * Sets the ancestor.
     *
     * @param string $ancestor
     */
    public function setAncestor(string $ancestor): void
    {
        $this->ancestor = $ancestor;
    }
}
