<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\Configuration;
use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\Propel;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Symfony\Component\Finder\Adapter\AbstractAdapter;

/**
 * Test class for Join.
 *
 * @author François Zaninotto
 * @version    $Id$
 */
class JoinTest extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Configuration::getCurrentConfigurationOrCreate()->setAdapter('default', new SqliteAdapter());
    }


    public function testEmptyConditions()
    {
        $j = new Join();
        $this->assertEquals([], $j->getConditions());
    }

    public function testAddCondition()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar');
        $this->assertEquals('=', $j->getOperator());
        $this->assertEquals('foo', $j->getLeftField());
        $this->assertEquals('bar', $j->getRightField());
    }

    public function testGetConditions()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar');
        $expect = [['left' => 'foo', 'operator' => '=', 'right' => 'bar']];
        $this->assertEquals($expect, $j->getConditions());
    }

    public function testAddConditionWithOperator()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar', '>=');
        $expect = [['left' => 'foo', 'operator' => '>=', 'right' => 'bar']];
        $this->assertEquals($expect, $j->getConditions());
    }

    public function testAddConditions()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar');
        $j->addCondition('baz', 'bal');
        $expect = [
            ['left' => 'foo', 'operator' => '=', 'right' => 'bar'],
            ['left' => 'baz', 'operator' => '=', 'right' => 'bal']
        ];
        $this->assertEquals(['=', '='], $j->getOperators());
        $this->assertEquals(['foo', 'baz'], $j->getLeftFields());
        $this->assertEquals(['bar', 'bal'], $j->getRightFields());
        $this->assertEquals($expect, $j->getConditions());
    }

    public function testAddExplicitConditionWithoutAlias()
    {
        $j = new Join();
        $j->addExplicitCondition('a', 'foo', null, 'b', 'bar', null);
        $this->assertEquals('=', $j->getOperator());
        $this->assertEquals('a.foo', $j->getLeftField());
        $this->assertEquals('b.bar', $j->getRightField());
        $this->assertEquals('a', $j->getLeftTableName());
        $this->assertEquals('b', $j->getRightTableName());
        $this->assertNull($j->getLeftTableAlias());
        $this->assertNull($j->getRightTableAlias());
        $this->assertEquals(1, $j->countConditions());
    }

    public function testAddExplicitconditionWithOneAlias()
    {
        $j = new Join();
        $j->setJoinType(Criteria::LEFT_JOIN);
        $j->addExplicitCondition('book', 'AUTHOR_ID', null, 'author', 'ID', 'a', Join::EQUAL);
        $params = [];
        $this->assertEquals($j->getClause($params), 'LEFT JOIN author a ON (book.AUTHOR_ID=a.ID)');
    }

    public function testAddExplicitConditionWithAlias()
    {
        $j = new Join();
        $j->addExplicitCondition('a', 'foo', 'Alias', 'b', 'bar', 'Blias');
        $this->assertEquals('=', $j->getOperator());
        $this->assertEquals('Alias.foo', $j->getLeftField());
        $this->assertEquals('Blias.bar', $j->getRightField());
        $this->assertEquals('a', $j->getLeftTableName());
        $this->assertEquals('b', $j->getRightTableName());
        $this->assertEquals('Alias', $j->getLeftTableAlias());
        $this->assertEquals('Blias', $j->getRightTableAlias());
    }

    public function testAddExplicitConditionWithOperator()
    {
        $j = new Join();
        $j->addExplicitCondition('a', 'foo', null, 'b', 'bar', null, '>=');
        $this->assertEquals('>=', $j->getOperator());
        $this->assertEquals('a.foo', $j->getLeftField());
        $this->assertEquals('b.bar', $j->getRightField());
    }

    public function testEmptyJoinType()
    {
        $j = new Join();
        $this->assertEquals(Join::INNER_JOIN, $j->getJoinType());
    }

    public function testSetJoinType()
    {
        $j = new Join();
        $j->setJoinType('foo');
        $this->assertEquals('foo', $j->getJoinType());
    }

    public function testSimpleConstructor()
    {
        $j = new Join('foo', 'bar', 'LEFT JOIN');
        $expect = [['left' => 'foo', 'operator' => '=', 'right' => 'bar']];
        $this->assertEquals($expect, $j->getConditions());
        $this->assertEquals('LEFT JOIN', $j->getJoinType());
    }

    public function testCompositeConstructor()
    {
        $j = new Join(['foo1', 'foo2'], ['bar1', 'bar2'], 'LEFT JOIN');
        $expect = [
            ['left' => 'foo1', 'operator' => '=', 'right' => 'bar1'],
            ['left' => 'foo2', 'operator' => '=', 'right' => 'bar2']
        ];
        $this->assertEquals($expect, $j->getConditions());
        $this->assertEquals('LEFT JOIN', $j->getJoinType());
    }

    public function testCountConditions()
    {
        $j = new Join();
        $this->assertEquals(0, $j->countConditions());
        $j->addCondition('foo', 'bar');
        $this->assertEquals(1, $j->countConditions());
        $j->addCondition('foo1', 'bar1');
        $this->assertEquals(2, $j->countConditions());
    }

    public function testEquality()
    {
        $j1 = new Join('foo', 'bar', 'INNER JOIN');
        $this->assertFalse($j1->equals(null), 'Join and null is not equal');

        $j2 = new Join('foo', 'bar', 'LEFT JOIN');
        $this->assertFalse($j1->equals($j2), 'INNER JOIN and LEFT JOIN are not equal');

        $j3 = new Join('foo', 'bar', 'INNER JOIN');
        $j3->addCondition('baz.foo', 'baz.bar');
        $this->assertFalse($j1->equals($j3), 'Joins with differend conditionsare not equal');

        $j4 = new Join('foo', 'bar', 'INNER JOIN');
        $j4->addExplicitCondition('book', 'AUTHOR_ID', null, 'author', 'ID', 'a', Join::EQUAL);
        $this->assertFalse($j1->equals($j4), 'Joins with differend clauses not equal');

        $j5 = new Join('foo', 'bar');
        $j6 = new Join('foo', 'bar');
        $this->assertTrue($j5->equals($j6), 'Joins without specified join type should be equal as they fallback to default join type');
    }
}
