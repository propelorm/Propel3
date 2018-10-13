<?php

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\EntityComparator;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Model\Diff\FieldComparator;
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the Field methods of the EntityComparator service class.
 *
 */
class EntityFieldComparatorTest extends TestCase
{

    /**
     * @var MysqlPlatform
     */
    protected $platform;

    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameFields()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);

        $this->assertNull(EntityComparator::computeDiff($t1, $t2));
    }

    public function testCompareNotSameFields()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Bar');
        $t2->addField($c2);

        $diff = EntityComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof EntityDiff);
    }

    public function testCompareAddedField()
    {
        $t1 = new Entity();
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getAddedFields()->size());
        $this->assertEquals(['Foo' => $c2], $tableDiff->getAddedFields()->toArray());
    }

    public function testCompareRemovedField()
    {
        $t1 = new Entity();
        $c1 = new Field('Bar');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $t2 = new Entity();

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getRemovedFields()->size());
        $this->assertEquals(['Bar' => $c1], $tableDiff->getRemovedFields()->toArray());
    }

    public function testCompareModifiedField()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $c1->setNotNull(false);
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getModifiedFields()->size());
        $columnDiff = FieldComparator::computeDiff($c1, $c2);
        $this->assertEquals(['Foo' => $columnDiff], $tableDiff->getModifiedFields()->toArray());
    }

    public function testCompareRenamedField()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Bar');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getRenamedFields()->size());
        $this->assertEquals([$c1, $c2], $tableDiff->getRenamedFields()->get('Foo'));
        $this->assertTrue($tableDiff->getAddedFields()->isEmpty());
        $this->assertTrue($tableDiff->getRemovedFields()->isEmpty());
    }

    public function testCompareSeveralFieldDifferences()
    {
        $t1 = new Entity();
        $c1 = new Field('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $c1->setNotNull(false);
        $t1->addField($c1);
        $c2 = new Field('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->setNotNull(true);
        $t1->addField($c2);
        $c3 = new Field('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c3->getDomain()->replaceSize(255);
        $t1->addField($c3);

        $t2 = new Entity();
        $c4 = new Field('col1');
        $c4->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c4->getDomain()->replaceScale(2);
        $c4->getDomain()->replaceSize(3);
        $c4->setNotNull(true);
        $c4->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c4);
        $c5 = new Field('col22');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c5->setNotNull(true);
        $t2->addField($c5);
        $c6 = new Field('col4');
        $c6->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $c6->getDomain()->setDefaultValue(new FieldDefaultValue('123', FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c6);

        // col1 was modified, col2 was renamed, col3 was removed, col4 was added
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(4, $nbDiffs);
        $this->assertEquals([$c2, $c5], $tableDiff->getRenamedFields()->get('col2'));
        $this->assertEquals($c6, $tableDiff->getAddedFields()->get('col4'));
        $this->assertEquals($c3, $tableDiff->getRemovedFields()->get('col3'));
        $columnDiff = FieldComparator::computeDiff($c1, $c4);
        $this->assertEquals($columnDiff, $tableDiff->getModifiedFields()->get('col1'));
    }

    public function testCompareSeveralRenamedSameFields()
    {
        $t1 = new Entity();
        $c1 = new Field('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $t1->addField($c1);
        $c2 = new Field('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c2->getDomain()->replaceSize(255);
        $t1->addField($c2);
        $c3 = new Field('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c3->getDomain()->replaceSize(255);
        $t1->addField($c3);

        $t2 = new Entity();
        $c4 = new Field('col4');
        $c4->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c4->getDomain()->replaceSize(255);
        $t2->addField($c4);
        $c5 = new Field('col5');
        $c5->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c5->getDomain()->replaceSize(255);
        $t2->addField($c5);
        $c6 = new Field('col3');
        $c6->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c6->getDomain()->replaceSize(255);
        $t2->addField($c6);

        // col1 and col2 were renamed
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(2, $nbDiffs);
        $this->assertEquals(['col1' => [$c1, $c4], 'col2' => [$c2, $c5]], $tableDiff->getRenamedFields()->toArray());
        $this->assertTrue($tableDiff->getAddedFields()->isEmpty());
        $this->assertTrue($tableDiff->getRemovedFields()->isEmpty());
        $this->assertTrue($tableDiff->getModifiedFields()->isEmpty());
    }
}
