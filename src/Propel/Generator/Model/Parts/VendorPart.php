<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Vendor;
use phootwork\collection\Map;

/**
 * Trait VendorPart
 *
 * @author Thomas Gossmann
 */
trait VendorPart
{
    /**
     * @var Map
     */
    protected $vendor;

    protected function initVendor()
    {
        $this->vendor = new Map();
    }

    /**
     * Adds vendor information to the current model
     *
     * @param Vendor $vendor
     * @return $this
     */
    public function addVendor(Vendor $vendor)
    {
        $this->vendor->set($vendor->getType(), $vendor);

        return $this;
    }

    /**
     * Returns a vendor object by its type (creates a new one, if type doesn't exist).
     *
     * @param string $type
     * @return Vendor
     */
    public function getVendorByType($type): Vendor
    {
        if (!$this->vendor->has($type)) {
            $this->addVendor(new Vendor($type));
        }

        return $this->vendor->get($type);
    }

    /**
     * Returns all vendor information.
     *
     * @return Vendor[]
     */
    public function getVendor(): array
    {
        return $this->vendor->toArray();
    }
}
