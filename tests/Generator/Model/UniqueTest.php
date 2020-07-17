<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Unique;

/**
 * Unit test suite for the Unique model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class UniqueTest extends ModelTestCase
{
    /**
     * @dataProvider provideEntitySpecificAttributes
     *
     */
    public function testCreateDefaultUniqueIndexName(string $entityName, int $maxFieldNameLength, string $indexName): void
    {
        $platform = $this->getPlatformMock(true, ['max_field_name_length' => $maxFieldNameLength]);
        $database = $this->getDatabaseMock('bookstore', ['platform' => $platform]);

        $entity = $this->getEntityMock($entityName, [
            'name' => $entityName,
            'unices'      => [ new Unique(), new Unique() ],
            'database'    => $database,
        ]);

        $index = new Unique();
        $index->setEntity($entity);

        $this->assertTrue($index->isUnique());
        $this->assertEquals($indexName, $index->getName());
    }

    public function provideEntitySpecificAttributes(): array
    {
        return [
            [ 'books', 64, 'books_u_no_fields' ],
            [ 'super_long_entity_name', 17, 'super_long_entity' ],
        ];
    }
}
