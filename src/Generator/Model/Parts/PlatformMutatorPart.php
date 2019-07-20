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

use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Config\GeneratorConfigInterface;

/**
 * Trait PlatformMutatorPart
 *
 * @author Thomas Gossmann
 */
trait PlatformMutatorPart
{
    use PlatformAccessorPart;

    /**
     * Sets the generator configuration
     *
     * @param GeneratorConfigInterface $generatorConfig
     * @return $this
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        $this->generatorConfig = $generatorConfig;

        if (!$this->platform) {
            $this->platform = $generatorConfig->createPlatformForDatabase();
        }

        return $this;
    }

    /**
     * @param PlatformInterface $platform
     * @return $this
     */
    public function setPlatform(PlatformInterface $platform)
    {
        $this->platform = $platform;
        return $this;
    }
}
