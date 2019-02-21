<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Entity;
use Propel\Generator\Schema\SchemaReader;
use Propel\Tests\TestCase;

/**
 * Base class for all Platform tests
 */
abstract class PlatformTestBase extends TestCase
{
    protected function getDatabaseFromSchema(string $schema): Database
    {
        $xtad = new SchemaReader();
        $appData = $xtad->parseString($schema, $this->getPlatform());

        return $appData->getDatabase();
    }

    protected function getEntityFromSchema(string $schema, $entityName = 'Foo'): Entity
    {
        return $this->getDatabaseFromSchema($schema)->getEntityByName($entityName);
    }
}
