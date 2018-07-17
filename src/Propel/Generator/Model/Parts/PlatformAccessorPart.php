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

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Trait PlatformAccessorPart
 *
 * @author Thomas Gossmann
 */
trait PlatformAccessorPart
{
    use SuperordinatePart;

    /** @var GeneratorConfigInterface */
    protected $generatorConfig;

    /** @var PlatformInterface */
    protected $platform;

    /**
     * Retrieves the configuration object.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        if (null !== $this->generatorConfig) {
            return $this->generatorConfig;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getGeneratorConfig')) {
            return $this->getSuperordinate()->getGeneratorConfig();
        }

        return null;
    }

    /**
     * @return PlatformInterface
     */
    public function getPlatform(): ?PlatformInterface
    {
        if (null !== $this->platform) {
            return $this->platform;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getPlatform')) {
            return $this->getSuperordinate()->getPlatform();
        }

        return null;
    }
}
