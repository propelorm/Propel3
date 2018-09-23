<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getPropWriter method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPropWriterMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $className = $this->getObjectClassName(true);

        $body = "
return \$this->getClassPropWriter('$className');
        ";

        $this->addMethod('getPropWriter')
            ->setBody($body);
    }
}