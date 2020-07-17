<?php declare(strict_types=1);
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
    private ModelFactory $modelFactory;

    public function setup(): void
    {
        $this->modelFactory = new ModelFactory();
    }

    /**
     * @dataProvider provideBehaviors
     */
    public function testCreateBehavior(string $name, string $class): void
    {
        $this->assertInstanceOf(
            "Propel\\Generator\\Behavior\\{$class}\\{$class}Behavior",
            $this->modelFactory->createBehavior(['name' => $name])
        );
    }

    public function provideBehaviors(): array
    {
        return [
            ['aggregate_field', 'AggregateField'],
            ['auto_add_pk', 'AutoAddPk'],
            ['concrete_inheritance', 'ConcreteInheritance'],
            ['delegate', 'Delegate'],
            ['nested_set', 'NestedSet'],
            ['query_cache', 'QueryCache'],
            ['sluggable', 'Sluggable'],
            ['sortable', 'Sortable'],
            ['timestampable', 'Timestampable'],
        ];
    }
}
