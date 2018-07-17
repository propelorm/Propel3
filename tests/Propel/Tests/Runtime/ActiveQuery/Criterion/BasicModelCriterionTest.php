<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\BasicModelCriterion;

/**
 * Test class for BasicModelCriterion.
 *
 * @author François Zaninotto
 */
class BasicModelCriterionTest extends BaseTestCase
{
    public function testAppendPsToAddsBindingInfoForNotNullValues()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL = ?', 'A.COL', 'foo');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL = :p1', $ps);
        $expected = [
            ['entity' => 'A', 'field' => 'COL', 'value' => 'foo']
        ];
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToBindingAValueToAClauseWithNoQuestionMark()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL = B.COL', 'A.COL', 'foo');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
        $this->assertEquals('A.COL = B.COL', $ps);
    }

    public function testAppendPsToAddsClauseWithoutBindingForNullValues()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL IS NULL', 'A.COL', null);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IS NULL', $ps);
        $this->assertEquals([], $params);
    }
}
