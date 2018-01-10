<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbMySQL adapter
 *
 * @see        BookstoreDataPopulator
 * @author William Durand
 */
class MysqlAdapterTest extends TestCaseFixtures
{
    public static function getConParams()
    {
        return [
            [
                [
                    'dsn' => 'dsn=my_dsn',
                    'settings' => [
                        'charset' => 'foobar'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getConParams
     */
    public function testPrepareParams($conparams)
    {
        $db = new TestableMysqlAdapter();
        $params = $db->prepareParams($conparams);

        $this->assertTrue(is_array($params));
        $this->assertEquals('dsn=my_dsn;charset=foobar', $params['dsn'], 'The given charset is in the DSN string');
        $this->assertArrayNotHasKey('charset', $params['settings'], 'The charset should be removed');
    }

    /**
     * @dataProvider getConParams
     */
    public function testNoSetNameQueryExecuted($conparams)
    {
        $db = new TestableMysqlAdapter();
        $params = $db->prepareParams($conparams);

        $settings = [];
        if (isset($params['settings'])) {
            $settings = $params['settings'];
        }

        $db->initConnection($this->getPdoMock(), $settings);
    }

    protected function getPdoMock()
    {
        $con = $this
            ->createMock('\Propel\Runtime\Connection\ConnectionInterface');

        $con
            ->expects($this->never())
            ->method('exec');

        return $con;
    }

    /**
     * @dataProvider dataApplyLimit
     */
    public function testApplyLimit($offset, $limit, $expectedSql)
    {
        $sql = '';

        $db = new MysqlAdapter();
        $db->applyLimit($sql, $offset, $limit);

        $this->assertEquals($expectedSql, $sql, 'Generated SQL does not match expected SQL');
    }

    public function dataApplyLimit()
    {
        return array(

            /*
                Offset & limit = 0
             */

            'Zero offset & limit' => array(
                'offset'      => 0,
                'limit'       => 0,
                'expectedSql' => ' LIMIT 0'
            ),

            /*
                Offset = 0
             */

            '32-bit limit' => array(
                'offset'      => 0,
                'limit'       => 4294967295,
                'expectedSql' => ' LIMIT 4294967295'
            ),
            '32-bit limit as a string' => array(
                'offset'      => 0,
                'limit'       => '4294967295',
                'expectedSql' => ' LIMIT 4294967295'
            ),

            '64-bit limit' => array(
                'offset'      => 0,
                'limit'       => 9223372036854775807,
                'expectedSql' => ' LIMIT 9223372036854775807'
            ),
            '64-bit limit as a string' => array(
                'offset'      => 0,
                'limit'       => '9223372036854775807',
                'expectedSql' => ' LIMIT 9223372036854775807'
            ),

            'Float limit' => array(
                'offset'      => 0,
                'limit'       => 123.9,
                'expectedSql' => ' LIMIT 123'
            ),
            'Float limit as a string' => array(
                'offset'      => 0,
                'limit'       => '123.9',
                'expectedSql' => ' LIMIT 123'
            ),

            'Negative limit' => array(
                'offset'      => 0,
                'limit'       => -1,
                'expectedSql' => ''
            ),
            'Non-numeric string limit' => array(
                'offset'      => 0,
                'limit'       => 'foo',
                'expectedSql' => ' LIMIT 0'
            ),
            'SQL injected limit' => array(
                'offset'      => 0,
                'limit'       => '3;DROP TABLE abc',
                'expectedSql' => ' LIMIT 3'
            ),

            /*
                Limit = 0
             */

            '32-bit offset' => array(
                'offset'      => 4294967295,
                'limit'       => 0,
                'expectedSql' => ' LIMIT 4294967295, 0'
            ),
            '32-bit offset as a string' => array(
                'offset'      => '4294967295',
                'limit'       => 0,
                'expectedSql' => ' LIMIT 4294967295, 0'
            ),

            '64-bit offset' => array(
                'offset'      => 9223372036854775807,
                'limit'       => 0,
                'expectedSql' => ' LIMIT 9223372036854775807, 0'
            ),
            '64-bit offset as a string' => array(
                'offset'      => '9223372036854775807',
                'limit'       => 0,
                'expectedSql' => ' LIMIT 9223372036854775807, 0'
            ),

            'Float offset' => array(
                'offset'      => 123.9,
                'limit'       => 0,
                'expectedSql' => ' LIMIT 123, 0'
            ),
            'Float offset as a string' => array(
                'offset'      => '123.9',
                'limit'       => 0,
                'expectedSql' => ' LIMIT 123, 0'
            ),

            'Negative offset' => array(
                'offset'      => -1,
                'limit'       => 0,
                'expectedSql' => ' LIMIT 0'
            ),
            'Non-numeric string offset' => array(
                'offset'      => 'foo',
                'limit'       => 0,
                'expectedSql' => ' LIMIT 0'
            ),
            'SQL injected offset' => array(
                'offset'      => '3;DROP TABLE abc',
                'limit'       => 0,
                'expectedSql' => ' LIMIT 3, 0'
            ),

            /*
                Offset & limit != 0
             */

            array(
                'offset'      => 4294967295,
                'limit'       => 999,
                'expectedSql' => ' LIMIT 4294967295, 999'
            ),
            array(
                'offset'      => '4294967295',
                'limit'       => 999,
                'expectedSql' => ' LIMIT 4294967295, 999'
            ),

            array(
                'offset'      => 9223372036854775807,
                'limit'       => 999,
                'expectedSql' => ' LIMIT 9223372036854775807, 999'
            ),
            array(
                'offset'      => '9223372036854775807',
                'limit'       => 999,
                'expectedSql' => ' LIMIT 9223372036854775807, 999'
            ),

            array(
                'offset'      => 123.9,
                'limit'       => 999,
                'expectedSql' => ' LIMIT 123, 999'
            ),
            array(
                'offset'      => '123.9',
                'limit'       => 999,
                'expectedSql' => ' LIMIT 123, 999'
            ),

            array(
                'offset'      => -1,
                'limit'       => 999,
                'expectedSql' => ' LIMIT 999'
            ),
            array(
                'offset'      => 'foo',
                'limit'       => 999,
                'expectedSql' => ' LIMIT 999'
            ),
            array(
                'offset'      => '3;DROP TABLE abc',
                'limit'       => 999,
                'expectedSql' => ' LIMIT 3, 999'
            ),
        );
    }
}

class TestableMysqlAdapter extends MysqlAdapter
{
    public function prepareParams($conparams)
    {
        return parent::prepareParams($conparams);
    }
}
