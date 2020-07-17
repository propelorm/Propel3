<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\collection\Map;
use Propel\Generator\Model\Vendor;

/**
 * Trait VendorPart
 *
 * @author Thomas Gossmann
 */
trait VendorPart
{
    protected Map $vendor;

    protected function initVendor()
    {
        $this->vendor = new Map();
    }

    /**
     * Adds vendor information to the current model
     *
     * @param Vendor $vendor
     */
    public function addVendor(Vendor $vendor): void
    {
        $this->vendor->set($vendor->getType(), $vendor);
    }

    /**
     * Returns a vendor object by its type or create a new one.
     *
     * @param string $type
     * @return Vendor
     */
    public function getVendorByType(string $type): Vendor
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
