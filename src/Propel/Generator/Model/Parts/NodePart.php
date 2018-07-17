<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;


/**
 * Trait NodePart
 * This trait contains some methods extracted from Field model. Imho, these method should be
 * removed, because superseded by NestedSetBehavior.
 *
 * @author Cristiano Cinotti
 */
trait NodePart
{
    private $isNodeKey;
    private $nodeKeySep;
    private $isNestedSetLeftKey;
    private $isNestedSetRightKey;
    private $isTreeScopeKey;

    /**
     * Sets whether or not the column is a node key of a tree.
     *
     * @param boolean $isNodeKey
     */
    public function setNodeKey($isNodeKey)
    {
        $this->isNodeKey = (Boolean) $isNodeKey;
    }

    /**
     * Returns whether or not the column is a node key of a tree.
     *
     * @return boolean
     */
    public function isNodeKey()
    {
        return $this->isNodeKey;
    }

    /**
     * Sets the separator for the node key column in a tree.
     *
     * @param string $sep
     */
    public function setNodeKeySep($sep)
    {
        $this->nodeKeySep = (string) $sep;
    }

    /**
     * Returns the node key column separator for a tree.
     *
     * @return string
     */
    public function getNodeKeySep()
    {
        return $this->nodeKeySep;
    }

    /**
     * Sets whether or not the column is the nested set left key of a tree.
     *
     * @param boolean $isNestedSetLeftKey
     */
    public function setNestedSetLeftKey($isNestedSetLeftKey)
    {
        $this->isNestedSetLeftKey = (Boolean) $isNestedSetLeftKey;
    }

    /**
     * Returns whether or not the column is a nested set key of a tree.
     *
     * @return boolean
     */
    public function isNestedSetLeftKey()
    {
        return $this->isNestedSetLeftKey;
    }

    /**
     * Set if the column is the nested set right key of a tree.
     *
     * @param boolean $isNestedSetRightKey
     */
    public function setNestedSetRightKey($isNestedSetRightKey)
    {
        $this->isNestedSetRightKey = (Boolean) $isNestedSetRightKey;
    }

    /**
     * Return whether or not the column is a nested set right key of a tree.
     *
     * @return boolean
     */
    public function isNestedSetRightKey()
    {
        return $this->isNestedSetRightKey;
    }

    /**
     * Sets whether or not the column is the scope key of a tree.
     *
     * @param boolean $isTreeScopeKey
     */
    public function setTreeScopeKey($isTreeScopeKey)
    {
        $this->isTreeScopeKey = (Boolean) $isTreeScopeKey;
    }

    /**
     * Returns whether or not the column is a scope key of a tree.
     *
     * @return boolean
     */
    public function isTreeScopeKey()
    {
        return $this->isTreeScopeKey;
    }

}