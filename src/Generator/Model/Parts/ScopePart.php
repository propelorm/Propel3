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
 * Trait ScopePart
 *
 * @author Thomas Gossmann
 */
trait ScopePart
{
    use SuperordinatePart;

    private Text $scope;

    /**
     * Sets scope
     *
     * @param string|Text $scope
     */
    public function setScope($scope)
    {
        $this->scope = new Text($scope);
    }

    /**
     * Returns scope
     *
     * @return Text
     */
    public function getScope(): Text
    {
        if (!isset($this->scope)) {
            $this->scope = new Text();
        }

        if ($this->scope->isEmpty() && $this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getScope')) {
            return $this->getSuperordinate()->getScope();
        }

        return $this->scope;
    }
}
