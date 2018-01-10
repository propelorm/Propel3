<?php
namespace Propel\Generator\Model\Parts;

use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Config\GeneratorConfigInterface;

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
