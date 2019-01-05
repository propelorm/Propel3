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

namespace Propel\Generator\Behavior\Archivable\Component\Repository;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ArchiveMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();
        $archiveClassName = $behavior->getArchiveEntity()->getFullName();
        $archiveRepositoryName = $this->getRepositoryClassNameForEntity($behavior->getArchiveEntity(), true);

        $body = "
\$session = \$this->getConfiguration()->getSession();
if (\$session->isNew(\$entity)) {
    throw new \\InvalidArgumentException('New objects cannot be archived. You must save the current object before calling archive().');
}

if (!\$archive = \$this->getArchive(\$entity)) {
    \$archive = new \\$archiveClassName();
}
\$entityMap = \$this->getConfiguration()->getEntityMap('{$behavior->getEntity()->getFullName()}');
\$entityMap->copyInto(\$entity, \$archive);
";

        if ($archivedAtField = $behavior->getArchivedAtField()) {
            $body .= "
\$archive->set{$archivedAtField->getMethodName()}(time());
";
        }

        $body .= "
\$session->persist(\$archive);

return \$archive;
";

        $this->addMethod('archive')
            ->setDescription('[Archivable] Archives this object and persists it (without commit())')
            ->addSimpleDescParameter('entity', $this->getEntity()->getFullName())
            ->setBody($body);
    }
}
