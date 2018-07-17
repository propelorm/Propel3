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

use Propel\Generator\Model\Domain;

/**
 * Trait DomainPart
 *
 * @author Cristiano Cinotti
 */
trait DomainPart
{
    /**
     * @var Domain
     */
    protected $domain;

    /**
     * @param Domain $domain
     *
     * @return $this
     */
    public function setDomain(Domain $domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return Domain
     */
    public function getDomain(): Domain
    {
        return $this->domain;
    }
}
