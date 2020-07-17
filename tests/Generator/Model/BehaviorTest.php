<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use org\bovigo\vfs\vfsStream;
use Propel\Generator\Exception\BehaviorNotFoundException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Entity;
use Propel\Generator\Schema\SchemaReader;

/**
 * Tests for Behavior class
 *
 * @author Martin Poeschl <mpoeschl@marmot.at>
 */
class BehaviorTest extends ModelTestCase
{
    public function testName()
    {
        $b = new Behavior();
        $this->assertEquals('', $b->getName(), 'Behavior name is null string by default');
        $b->setName('foo');
        $this->assertEquals('foo', $b->getName(), 'setName() sets the name, and getName() gets it');
    }

    public function testEntity()
    {
        $b = new Behavior();
        $this->assertNull($b->getEntity(), 'Behavior Entity is null by default');
        $t = new Entity();
        $t->setName('FooEntity');
        $b->setEntity($t);
        $this->assertEquals($b->getEntity(), $t, 'setEntity() sets the name, and getEntity() gets it');
    }

    public function testParameters()
    {
        $b = new Behavior();
        $this->assertEquals([], $b->getParameters()->toArray(), 'Behavior parameters is an empty array by default');
        $b->addParameter(['name' => 'foo', 'value' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $b->getParameters()->toArray(), 'addParameter() sets a parameter from an associative array');
        $b->addParameter(['name' => 'foo2', 'value' => 'bar2']);
        $this->assertEquals(['foo' => 'bar', 'foo2' => 'bar2'], $b->getParameters()->toArray(), 'addParameter() adds a parameter from an associative array');
        $b->addParameter(['name' => 'foo', 'value' => 'bar3']);
        $this->assertEquals(['foo' => 'bar3', 'foo2' => 'bar2'], $b->getParameters()->toArray(), 'addParameter() changes a parameter from an associative array');
        $this->assertEquals('bar3', $b->getParameter('foo'), 'getParameter() retrieves a parameter value by name');
        $b->setParameters(['foo3' => 'bar3', 'foo4' => 'bar4']);
        $this->assertEquals(['foo3' => 'bar3', 'foo4' => 'bar4'], $b->getParameters()->toArray(), 'setParameters() changes the whole parameter array');
    }

    public function testSchemaReader()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <entity name="entity1">
    <field name="id" type="INTEGER" primaryKey="true" />
    <field name="title" type="VARCHAR" size="100" primaryString="true" />
    <field name="created_on" type="TIMESTAMP" />
    <field name="updated_on" type="TIMESTAMP" />
    <behavior name="timestampable">
      <parameter name="create_field" value="created_on" />
      <parameter name="update_field" value="updated_on" />
    </behavior>
  </entity>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $appData->getPlatform()->doFinalInitialization($appData);
        $entity = $appData->getDatabase('test1')->getEntityByName('Entity1');
        $behaviors = $entity->getBehaviors();
        $this->assertEquals(1, count($behaviors), 'SchemaReader ads as many behaviors as there are behaviors tags');
        $behavior = $entity->getBehavior('timestampable');
        $this->assertEquals('Entity1', $behavior->getEntity()->getName(), 'SchemaReader sets the behavior entity correctly');
        $this->assertEquals(
            ['create_field' => 'created_on', 'update_field' => 'updated_on', 'disable_created_at' => false, 'disable_updated_at' => false],
            $behavior->getParameters()->toArray(),
            'SchemaReader sets the behavior parameters correctly'
        );
    }

    public function testUnknownBehavior()
    {
        $this->expectException(BehaviorNotFoundException::class);

        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <entity name="table1">
    <field name="id" type="INTEGER" primaryKey="true" />
    <behavior name="foo" />
  </entity>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
    }

    public function testModifyEntity()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <entity name="table2">
    <field name="id" type="INTEGER" primaryKey="true" />
    <field name="title" type="VARCHAR" size="100" primaryString="true" />
    <behavior name="timestampable" />
  </entity>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $appData->getPlatform()->doFinalInitialization($appData);
        $entity = $appData->getDatabase('test1')->getEntityByName('Table2');
        $this->assertEquals(4, $entity->getFields()->size(), 'A behavior can modify its table by implementing modifyEntity()');
    }

    public function testModifyDatabase()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <behavior name="timestampable" />
  <entity name="table1">
    <field name="id" type="INTEGER" primaryKey="true" />
  </entity>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $appData->getPlatform()->doFinalInitialization($appData);
        $entity = $appData->getDatabase('test1')->getEntityByName('Table1');
        $this->assertTrue(array_key_exists('timestampable', $entity->getBehaviors()), 'A database behavior is automatically copied to all its table');
    }

    public function testGetColumnForParameter()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <entity name="table1">
    <field name="id" type="INTEGER" primaryKey="true" />
    <field name="title" type="VARCHAR" size="100" primaryString="true" />
    <field name="created_on" type="TIMESTAMP" />
    <field name="updated_on" type="TIMESTAMP" />
    <behavior name="timestampable">
      <parameter name="create_field" value="created_on" />
      <parameter name="update_field" value="updated_on" />
    </behavior>
  </entity>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $entity = $appData->getDatabase('test1')->getEntityByName('Table1');
        $behavior = $entity->getBehavior('timestampable');
        $this->assertEquals($entity->getFieldByName('created_on'), $behavior->getFieldForParameter('create_field'), 'getFieldForParameter() returns the configured field for behavior based on a parameter name');
    }
}
