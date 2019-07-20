<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Behavior\Archivable\Component\ActiveRecordTrait;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();
        $archiveClassName = $behavior->getArchiveEntity()->getFullName();

        $body = "
\$archive = \$this->getRepository()->archive(\$this);
\$this->getPropelConfiguration()->getSession()->commit();
return \$archive;
";

        $this->addMethod('archive')
            ->setDescription('[Archivable] Archives this object and saves it.')
            ->setType($archiveClassName)
            ->setBody($body);
    }
}
