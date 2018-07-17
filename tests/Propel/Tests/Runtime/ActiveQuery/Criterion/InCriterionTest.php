<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\Collection\ArrayCollection;
use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\InCriterion;

/**
 * Test class for InCriterion.
 *
 * @author François Zaninotto
 */
class InCriterionTest extends BaseTestCase
{
    public function testAppendPsToCreatesAnInConditionByDefault()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', ['foo']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['entity' => 'A', 'field' => 'COL', 'value' => 'foo']
        ];
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesANotInConditionWhenSpecified()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', ['foo'], Criteria::NOT_IN);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT IN (:p1)', $ps);
        $expected = [
            ['entity' => 'A', 'field' => 'COL', 'value' => 'foo']
        ];
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesAnInConditionUsingAColumnAlias()
    {
        $cton = new InCriterion(new Criteria(), 'my_alias', ['foo']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('my_alias IN (:p1)', $ps);
        $expected = [
            ['entity' => null, 'field' => 'my_alias', 'value' => 'foo']
        ];
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesAnInConditionUsingATableAlias()
    {
        $c = new Criteria();
        $c->addAlias('bar_alias', 'bar');
        $cton = new InCriterion($c, 'bar_alias.COL', ['foo']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('bar_alias.COL IN (:p1)', $ps);
        $expected = [
            ['entity' => 'bar', 'field' => 'COL', 'value' => 'foo']
        ];
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithArrayValueCreatesAnInCondition()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', ['foo', 'bar']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1,:p2)', $ps);
        $expected = [
            ['entity' => 'A', 'field' => 'COL', 'value' => 'foo'],
            ['entity' => 'A', 'field' => 'COL', 'value' => 'bar']
        ];
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithScalarValueCreatesAnInCondition()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', 'foo');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['entity' => 'A', 'field' => 'COL', 'value' => 'foo']
        ];
        $this->assertEquals($expected, $params);
    }

    public static function providerForNotEmptyValues()
    {
        return [
            [''],
            [0],
            [true],
            [false]
        ];
    }

    /**
     * @dataProvider providerForNotEmptyValues
     */
    public function testAppendPsToWithNotEmptyValueCreatesAnInCondition($notEmptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $notEmptyValue);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['entity' => 'A', 'field' => 'COL', 'value' => $notEmptyValue]
        ];
        $this->assertEquals($expected, $params);
    }

    public static function providerForEmptyValues()
    {
        return [
            [[]],
            [null]
        ];
    }

    /**
     * @dataProvider providerForEmptyValues
     */
    public function testAppendPsToWithInAndEmptyValueCreatesAnAlwaysFalseCondition($emptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $emptyValue);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('1<>1', $ps);
        $expected = [];
        $this->assertEquals($expected, $params);
    }

    /**
      * @dataProvider providerForEmptyValues
      */
    public function testAppendPsToWithNotInAndEmptyValueCreatesAnAlwaysTrueCondition($emptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $emptyValue, Criteria::NOT_IN);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('1=1', $ps);
        $expected = [];
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithArrayCollection()
    {
        $collection = new ArrayCollection(['foo']);
        $cton = new InCriterion(new Criteria(), 'A.COL', $collection);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['entity' => 'A', 'field' => 'COL', 'value' => 'foo']
        ];
        $this->assertEquals($expected, $params);
    }
}
