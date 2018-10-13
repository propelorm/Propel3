<?php

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Diff\FieldComparator;
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the FieldComparator service class.
 *
 */
class FieldComparatorTest extends TestCase
{
    /** @var MysqlPlatform */
    private $platform;

    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareNoDifference()
    {
        $c1 = new Field();
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $c2 = new Field();
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $this->assertTrue(FieldComparator::compareFields($c1, $c2)->isEmpty());
    }

    public function testCompareType()
    {
        $c1 = new Field();
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c2 = new Field();
        $c2->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $expectedChangedProperties = [
            'type'    => ['VARCHAR', 'LONGVARCHAR'],
            'sqlType' => ['VARCHAR', 'TEXT'],
        ];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareScale()
    {
        $c1 = new Field();
        $c1->getDomain()->replaceScale(2);
        $c2 = new Field();
        $c2->getDomain()->replaceScale(3);
        $expectedChangedProperties = ['scale' => [2, 3]];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareSize()
    {
        $c1 = new Field();
        $c1->getDomain()->replaceSize(2);
        $c2 = new Field();
        $c2->getDomain()->replaceSize(3);
        $expectedChangedProperties = ['size' => [2, 3]];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareSqlType()
    {
        $c1 = new Field();
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2 = new Field();
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->getDomain()->setSqlType('INTEGER(10) UNSIGNED');
        $expectedChangedProperties = ['sqlType' => ['INTEGER', 'INTEGER(10) UNSIGNED']];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareNotNull()
    {
        $c1 = new Field();
        $c1->setNotNull(true);
        $c2 = new Field();
        $c2->setNotNull(false);
        $expectedChangedProperties = ['notNull' => [true, false]];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueToNull()
    {
        $c1 = new Field();
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $c2 = new Field();
        $expectedChangedProperties = [
            'defaultValueType' => [FieldDefaultValue::TYPE_VALUE, null],
            'defaultValueValue' => [123, null]
        ];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueFromNull()
    {
        $c1 = new Field();
        $c2 = new Field();
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'defaultValueType' => [null, FieldDefaultValue::TYPE_VALUE],
            'defaultValueValue' => [null, 123]
        ];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueValue()
    {
        $c1 = new Field();
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $c2 = new Field();
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(456, FieldDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'defaultValueValue' => [123, 456]
        ];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueType()
    {
        $c1 = new Field();
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $c2 = new Field();
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_EXPR));
        $expectedChangedProperties = [
            'defaultValueType' => [FieldDefaultValue::TYPE_VALUE, FieldDefaultValue::TYPE_EXPR]
        ];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    /**
     * @see http://www.propelorm.org/ticket/1141
     */
    public function testCompareDefaultExrpCurrentTimestamp()
    {
        $c1 = new Field();
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue("NOW()", FieldDefaultValue::TYPE_EXPR));
        $c2 = new Field();
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue("CURRENT_TIMESTAMP", FieldDefaultValue::TYPE_EXPR));
        $this->assertTrue(FieldComparator::compareFields($c1, $c2)->isEmpty());
    }

    public function testCompareAutoincrement()
    {
        $c1 = new Field();
        $c1->setAutoIncrement(true);
        $c2 = new Field();
        $c2->setAutoIncrement(false);
        $expectedChangedProperties = ['autoIncrement' => [true, false]];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }

    public function testCompareMultipleDifferences()
    {
        $c1 = new Field();
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c1->setNotNull(false);
        $c2 = new Field();
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'type' => ['INTEGER', 'DOUBLE'],
            'sqlType' => ['INTEGER', 'DOUBLE'],
            'scale' => [null, 2],
            'size' => [null, 3],
            'notNull' => [false, true],
            'defaultValueType' => [null, FieldDefaultValue::TYPE_VALUE],
            'defaultValueValue' => [null, 123]
        ];
        $this->assertEquals($expectedChangedProperties, FieldComparator::compareFields($c1, $c2)->toArray());
    }
}
