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

namespace Propel\Generator\Behavior\Archivable\Component\ActiveRecordTrait;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

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

        $body = "
\$this->getRepository()->populateFromArchive(\$this, \$archive);
";

        $this->addMethod('populateFromArchive')
            ->setDescription('[Archivable] Populates the object based on a $archive object.')
            ->addSimpleParameter('archive', 'object')
            ->setType($archiveClassName)
            ->setBody($body);
    }
}
