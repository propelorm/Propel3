<?php

namespace Propel\Generator\Builder\Om\Component\Proxy;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the __get/__set method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class MagicMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {

        $body = '
';

        $loadableProperties = [];

        foreach ($this->getEntity()->getFields() as $field) {
            $loadableProperties[] = $field->getName();
        }


        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            foreach ($crossRelation->getRelations() as $relation) {
                $loadableProperties[] = $this->getCrossRelationRelationVarName($relation);
            }
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $this->getRelationVarName($relation);
            $loadableProperties[] = $relationName;
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $relationName = $this->getRefRelationCollVarName($relation);
            $loadableProperties[] = $relationName;
        }


        $getBody = $this->getCodeForFields($loadableProperties) . "
return \$this->\$name;
";
        $this->addMethod('__get')
            ->addSimpleParameter('name')
            ->setBody($getBody);

        $setBody = "
\$this->\$name = \$value;
";
        $this->addMethod('__set')
            ->addSimpleParameter('name')
            ->addSimpleParameter('value')
            ->setBody($setBody);


        $debugInfo = '
$fn = \\Closure::bind(function(){
    return get_object_vars($this);
}, $this, get_parent_class($this)); 
return $fn();
        ';

        $this->addProperty('__duringInitializing__', false, 'public');
        $this->addProperty('_repository', false, 'private');

        $this->addMethod('__debugInfo')->setBody($debugInfo);
    }

    /**
     * @param array $loadableProperties
     * @return string
     */
    protected function getCodeForFields($loadableProperties)
    {
        $body = '';
        $codePerField = [];
        foreach ($loadableProperties as $fieldName) {
            $fieldLazyLoading = "\$this->_repository->getEntityMap()->loadField(\$this, '$fieldName');";
            $codePerField[$fieldName] = $fieldLazyLoading;
        }

        foreach ($codePerField as $fieldName => $code) {
            $body .= "
if (!isset(\$this->__duringInitializing__) && '{$fieldName}' === \$name && !isset(\$this->{$fieldName})) {

    \$this->__duringInitializing__ = true;

    $code

    unset(\$this->__duringInitializing__);
}
";
        }

        return $body;
    }
}