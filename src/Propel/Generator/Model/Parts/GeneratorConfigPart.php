<?php
namespace Propel\Generator\Model\Parts;

use Propel\Generator\Config\GeneratorConfigInterface;

trait GeneratorConfigPart {

    use SuperordinatePart;

    /**
     * Retrieves the configuration object.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getGeneratorConfig')) {
            return $this->getSuperordinate()->getGeneratorConfig();
        }

        return null;
    }
}

