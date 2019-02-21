<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

declare(strict_types=1);

namespace Propel\Generator\Behavior\ConcreteInheritance\Component;

use Propel\Generator\Behavior\ConcreteInheritance\ConcreteInheritanceBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

class CopyToParentMethod extends BuildComponent
{
    public function process()
    {
        /** @var ConcreteInheritanceBehavior $behavior */
        $behavior = $this->getBehavior();
        $parentEntity = $behavior->getParentEntity();

        $body = <<<'EOF'
$parentProperties = get_object_vars($parentEntity);
$parentWriter = $this
    ->getConfiguration()
    ->getEntityMap('%s')
    ->getPropWriter();

$reader = $this
    ->getEntityMap()
    ->getPropReader();

foreach ($parentProperties as $propertyName => $defaultValue) {
    $parentWriter($parentEntity, $propertyName, $reader($entity, $propertyName));
}
EOF;

        $this->addMethod('copyToParent')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleParameter('parentEntity', 'object')
            ->setBody(sprintf($body, $parentEntity->getFullName()));
    }
}
