<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Model;


use Propel\Generator\Model\ModelFactory;

class ModelFactoryTest extends ModelTestCase
{
    /** @var ModelFactory */
    private $modelFactory;

    public function setUp()
    {
        $this->modelFactory = new ModelFactory();
    }

    /**
     * @dataProvider provideBehaviors
     */
    public function testCreateBehavior($name, $class)
    {
        $type = sprintf(
            'Propel\Generator\Behavior\%s\%sBehavior',
            $class,
            $class
        );

        $behavior = $this->modelFactory->createBehavior(['name' => $name]);

        $this->assertInstanceOf($type, $behavior);
    }

    public function provideBehaviors()
    {
        return array(
            array('aggregate_field', 'AggregateField'),
            array('auto_add_pk', 'AutoAddPk'),
            array('concrete_inheritance', 'ConcreteInheritance'),
            array('delegate', 'Delegate'),
            array('nested_set', 'NestedSet'),
            array('query_cache', 'QueryCache'),
            array('sluggable', 'Sluggable'),
            array('sortable', 'Sortable'),
            array('timestampable', 'Timestampable'),
        );
    }
}
