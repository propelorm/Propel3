<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Index;

/**
 * Unit test suite for the Index model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class IndexTest extends ModelTestCase
{
    public function testCreateNamedIndex()
    {
        $index = new Index('foo_idx');
        $index->setEntity($this->getEntityMock('db_books'));

        $this->assertEquals('foo_idx', $index->getName());
        $this->assertFalse($index->isUnique());
        $this->assertInstanceOf('Propel\Generator\Model\Entity', $index->getEntity());
        $this->assertSame('db_books', $index->getEntity()->getName());
        $this->assertCount(0, $index->getFields());
        $this->assertTrue($index->getFields()->isEmpty());
    }

    /**
     * @dataProvider provideEntitySpecificAttributes
     *
     */
    public function testCreateDefaultIndexName($tableName, $maxFieldNameLength, $indexName)
    {
        $platform = $this->getPlatformMock(true, ['max_field_name_length' => $maxFieldNameLength]);
        $database = $this->getDatabaseMock('bookstore', ['platform' => $platform]);

        $table = $this->getEntityMock($tableName, [
            'common_name' => $tableName,
            'indices'     => [ new Index(), new Index() ],
            'database'    => $database,
        ]);

        $index = new Index();
        $index->setEntity($table);

        $this->assertSame($indexName, $index->getName());
    }

    public function provideEntitySpecificAttributes()
    {
        return [
            [ 'books', 64, 'books_i_no_fields' ],
            [ 'super_long_table_name', 16, 'super_long_table' ],
        ];
    }

    public function testAddIndexedFields()
    {
        $columns = [
            $this->getFieldMock('foo', [ 'size' => 100 ]),
            $this->getFieldMock('bar', [ 'size' => 5   ]),
            $this->getFieldMock('baz', [ 'size' => 0   ])
        ];

        $index = new Index();
        $index->setEntity($this->getEntityMock('index_entity'));
        $index->addFields($columns);

        $this->assertFalse($index->getFields()->isEmpty());
        $this->assertCount(3, $index->getFields());
        $this->assertSame(100, $index->getField('foo')->getSize());
        $this->assertSame(5, $index->getField('bar')->getSize());
        $this->assertEquals(0, $index->getField('baz')->getSize());
    }

    public function testNoFieldAtFirstPosition()
    {
        $index = new Index();

        $this->assertFalse($index->hasFieldAtPosition(0, 'foo'));
    }

    /**
     * @dataProvider provideFieldAttributes
     */
    public function testNoFieldAtPositionCaseSensitivity($name, $case)
    {
        $index = new Index();
        $index->setEntity($this->getEntityMock('db_books'));
        $index->addField($this->getFieldMock('foo', [ 'size' => 5 ]));

        $this->assertFalse($index->hasFieldAtPosition(0, $name, 5, $case));
    }

    public function provideFieldAttributes()
    {
        return [
            [ 'bar', false ],
            [ 'BAR', true ],
        ];
    }

    public function testNoSizedFieldAtPosition()
    {
        $size = 5;

        $index = new Index();
        $index->setEntity($this->getEntityMock('db_books'));
        $index->addField($this->getFieldMock('foo', [ 'size' => $size ]));

        $size++;
        $this->assertFalse($index->hasFieldAtPosition(0, 'foo', $size));
    }

    public function testHasFieldAtFirstPosition()
    {
        $index = new Index();
        $index->setEntity($this->getEntityMock('db_books'));
        $index->addField($this->getFieldMock('foo', [ 'size' => 0 ]));

        $this->assertTrue($index->hasFieldAtPosition(0, 'foo'));
    }
}
