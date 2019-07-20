<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use Propel\Common\Collection\Map;

/**
 * Trait Attributes part.
 * Methods to manage a collection of attributes, taken from the schema.
 *
 * @author Cristiano Cinotti
 */
trait AttributesPart
{
    /**
     * @var Map
     */
    protected $attributes;

    /**
     * Returns all definition attributes.
     *
     * @return Map
     */
    public function getAttributes(): Map
    {
        return $this->attributes;
    }

    /**
     * Sets an element with the given key.
     *
     * @param string $key
     * @param $element
     */
    public function setAttribute(string $key, $element)
    {
       $this->attributes->set($key, $element);
    }

    /**
     * Returns a particular attribute by a case-insensitive name.
     *
     * If the attribute is not set, then the second default value is
     * returned instead.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name, $default);
    }
}
