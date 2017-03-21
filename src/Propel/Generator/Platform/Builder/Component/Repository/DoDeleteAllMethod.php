<?php

namespace Propel\Generator\Platform\Builder\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;

/**
 * Adds the doDeleteAll method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class DoDeleteAllMethod extends BuildComponent
{
    public function process()
    {
        $entity = $this->getEntity();

        $query = sprintf(
            'DELETE FROM %s',
            $this->quoteIdentifier($entity->getTableName())
        );

        $dbName = $entity->getDatabase()->getName();

        $body = "
        \$connection = \$this->getConfiguration()->getConnectionManager('$dbName')->getWriteConnection();
        \$sql = '$query';
        try {
            \$stmt = \$connection->prepare(\$sql);
            \$stmt->execute();
        } catch (Exception \$e) {
            \$this->getConfiguration()->log(\$e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute DELETE statement [%s]', \$sql), 0, \$e);
        }
        ";

        if ($entity->isReadOnly()) {
$body = <<<EOF
throw new BadMethodCallException('Readonly entity cannot be deleted');
EOF;
            $this->useClass('Propel\Runtime\Exception\BadMethodCallException');
        }

        $this->addMethod('doDeleteAll', 'protected')
            ->setDescription('doDeleteAll implementation for SQL Platforms')
            ->setBody($body);
    }
}