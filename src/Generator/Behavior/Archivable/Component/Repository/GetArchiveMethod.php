<?php declare(strict_types=1);
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
class GetArchiveMethod extends BuildComponent
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
    return null;
}

/** @var \\$archiveRepositoryName \$archiveRepository */
\$archiveRepository = \$this->getConfiguration()->getRepository('$archiveClassName');

\$archive = \$archiveRepository->createQuery()
    ->filterByPrimaryKey(\$this->getEntityMap()->getPrimaryKey(\$entity))
    ->findOne();

return \$archive;
";

        $this->addMethod('getArchive')
            ->setDescription('[Archivable] returns archived version.')
            ->addSimpleDescParameter('entity', $this->getEntity()->getFullName())
            ->setType($archiveClassName.'|null')
            ->setBody($body);
    }
}
