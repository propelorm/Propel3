<?php
namespace Propel\Generator\Model\Parts;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Platform\PlatformInterface;

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
