<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Tests\Bookstore\BookstoreQuery;

/**
 * Adds the remove method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RemoveMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $entityClassName = $this->getObjectClassName();

        $body = <<<EOF
\$session = \$this->getConfiguration()->getSession();
\$session->remove(\$entity);
\$session->commit();
EOF;

        if ($this->getEntity()->isReadOnly()) {
            $body = <<<EOF
throw new BadMethodCallException('Readonly entity cannot be deleted');
EOF;
            $this->useClass('Propel\Runtime\Exception\BadMethodCallException');
        }

        $this->addMethod('remove')
            ->addSimpleParameter('entity', $entityClassName)
            ->setDescription("Removes a instance of $entityClassName.")
            ->setBody($body);
    }
}
