<?php


namespace Propel\Generator\Platform\Builder;

use Propel\Generator\Builder\Om\AbstractBuilder;

class Repository extends AbstractBuilder
{
    public function buildClass()
    {
        $this->applyComponent('Repository\\DoFindMethod');
        $this->applyComponent('Repository\\DoDeleteAllMethod');
    }
}
