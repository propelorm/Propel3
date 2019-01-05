<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Behavior\Archivable\Component\Repository;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PopulateFromArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();
        $archiveClassName = $behavior->getArchiveEntity()->getFullName();
        $this->getDefinition()->declareUse($archiveClassName);

        $body = "
\$this->getConfiguration()->getEntityMap('$archiveClassName')->copyInto(\$archive, \$entity);
";

        $this->addMethod('populateFromArchive')
            ->setDescription('[Archivable] Populates the $entity object based on a $archive object.')
            ->addSimpleDescParameter('entity', $this->getEntity()->getFullName())
            ->addSimpleDescParameter('archive', $archiveClassName)
            ->setBody($body);
    }
}
