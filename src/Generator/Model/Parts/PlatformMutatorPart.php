<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

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
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig): void
    {
        $this->generatorConfig = $generatorConfig;

        if (!isset($this->platform)) {
            $this->platform = $generatorConfig->createPlatformForDatabase();
        }
    }

    /**
     * @param PlatformInterface $platform
     */
    public function setPlatform(PlatformInterface $platform): void
    {
        $this->platform = $platform;
    }
}
