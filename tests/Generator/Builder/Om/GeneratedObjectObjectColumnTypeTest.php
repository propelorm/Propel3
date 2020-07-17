<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Map\ComplexColumnTypeEntity1EntityMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Configuration;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for object column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectObjectColumnTypeTest extends TestCase
{
    /**
     * @var Configuration
     */
    protected $configuration;

    public function setUp(): void
    {
        if (!class_exists('ComplexColumnTypeEntity1')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_1" activeRecord="true">
    <entity name="complex_column_type_entity_1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="OBJECT" />
    </entity>
</database>
EOF;
            $this->configuration = QuickBuilder::buildSchema($schema);
        }
    }

    public function testObjectColumnType()
    {
        $e = new \ComplexColumnTypeEntity1();
        $this->assertNull($e->getBar(), 'object columns are null by default');
        $c = new FooColumnValue();
        $c->bar = 1234;
        $e->setBar($c);
        $this->assertEquals($c, $e->getBar(), 'object columns can store objects');
        $e->setBar(null);
        $this->assertNull($e->getBar(), 'object columns are nullable');
        $e->setBar($c);
        $e->save();
        $this->configuration->getSession()->clearFirstLevelCache();
        $e = \ComplexColumnTypeEntity1Query::create()->findOne();
        $this->assertEquals($c, $e->getBar(), 'object columns are persisted');
    }

    public function testGetterDoesNotKeepValueBetweenTwoHydrationsWhenUsingOnDemandFormatter()
    {
        \ComplexColumnTypeEntity1Query::create()->deleteAll();
        $e = new \ComplexColumnTypeEntity1();
        $e->setBar((object) [
            'a' =>1,
            'b' => 2
        ]);
        $e->save();

        $e = new \ComplexColumnTypeEntity1();
        $e->setBar((object) [
            'a' => 3,
            'b' => 4
        ]);
        $e->save();

        $q = \ComplexColumnTypeEntity1Query::create()
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find();

        $objects = [];
        foreach ($q as $e) {
            $objects[] = $e->getBar();
        }
        $this->assertNotEquals($objects[0], $objects[1]);
    }
}

class FooColumnValue
{
    public $bar;
}
