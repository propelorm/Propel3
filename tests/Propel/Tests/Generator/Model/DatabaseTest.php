<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\ModelFactory;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Model\Model;

/**
 * Unit test suite for Database model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DatabaseTest extends ModelTestCase
{
    public function testCreateNewDatabase()
    {
        $database = new Database('bookstore');

        $this->assertSame('bookstore', $database->getName());
        $this->assertSame(Model::DEFAULT_STRING_FORMAT, $database->getStringFormat());
        $this->assertSame(Model::DEFAULT_ID_METHOD, $database->getIdMethod());
        $this->assertEmpty($database->getScope());
        $this->assertNull($database->getSchema());
        $this->assertNull($database->getDomain('BOOLEAN'));
        $this->assertNull($database->getGeneratorConfig());
        $this->assertEquals(0, $database->getEntitySize());
        $this->assertEquals(0, $database->countEntities());
        $this->assertFalse($database->isHeavyIndexing());
        $this->assertFalse($database->hasEntityByName('foo'));
        $this->assertFalse($database->hasBehavior('foo'));
        $this->assertNull($database->getBehavior('foo'));
    }

    public function testSetParentSchema()
    {
        $schema = $this->getSchemaMock();
        $database = new Database();
        $database->setSchema($schema);

        $this->assertInstanceOf('Propel\Generator\Model\Schema', $database->getSchema());
        $this->assertSame($schema, $database->getSchema());
    }

     public function testAddBehavior()
     {
         $behavior = $this->getBehaviorMock('foo');

         $database = new Database();

         $this->assertInstanceOf('Propel\Generator\Model\Database', $database->addBehavior($behavior), 'Fluent api');
         $this->assertInstanceOf('Propel\Generator\Model\Behavior', $database->getBehavior('foo'));
         $this->assertSame($behavior, $database->getBehavior('foo'));
         $this->assertTrue($database->hasBehavior('foo'));
     }

     public function testGetNextEntityBehavior()
     {
         $entity1 = $this->getEntityMock('books', array('behaviors' => array(
             $this->getBehaviorMock('foo', array(
                 'is_entity_modified'  => false,
                'modification_order' => 2,
             )),
             $this->getBehaviorMock('bar', array(
                 'is_entity_modified'  => false,
                'modification_order' => 1,
             )),
             $this->getBehaviorMock('baz', array('is_entity_modified'  => true)),
         )));

         $entity2 = $this->getEntityMock('authors', array('behaviors' => array(
             $this->getBehaviorMock('mix', array(
                 'is_entity_modified'  => false,
                 'modification_order' => 1,
             )),
         )));

         $database = new Database();
         $database->addEntity($entity1);
         $database->addEntity($entity2);

         $behavior = $database->getNextEntityBehavior();

         $this->assertInstanceOf('Propel\Generator\Model\Behavior', $behavior);
         $this->assertSame('bar', $behavior->getName());
     }

     public function testCantGetNextEntityBehavior()
     {
         $entity1 = $this->getEntityMock('books', array('behaviors' => array(
             $this->getBehaviorMock('foo', array('is_entity_modified' => true)),
         )));

         $database = new Database();
         $database->addEntity($entity1);

         $behavior = $database->getNextEntityBehavior();

         $this->assertNull($database->getNextEntityBehavior());
     }

    public function testCantGetEntity()
    {
        $database = new Database();

        $this->assertFalse($database->hasEntityByName('foo'));
        $this->assertNull($database->getEntityByName('foo'));
    }

    public function testAddNamespacedEntity()
    {
        $entity = $this->getEntityMock('books', ['namespace' => '\Acme']);

        $database = new Database();
        $database->addEntity($entity);

        $this->assertTrue($database->hasEntityByName('books'));
    }

    public function testAddEntity()
    {
        $entity = $this->getEntityMock('books', [
            'namespace' => 'Acme\Model',
        ]);

        $database = new Database();
        $database->setNamespace('Acme\Model');
        $database->addEntity($entity);

        $this->assertSame(1, $database->countEntities());
        $this->assertCount(1, $database->getEntitiesForSql());

        $this->assertTrue($database->hasEntityByName('books'));
        $this->assertTrue($database->hasEntityByName('books'));
        $this->assertFalse($database->hasEntityByName('BOOKS'));
        $this->assertSame($entity, $database->getEntityByName('books'));
    }

    public function testAddSameEntityTwice()
    {
        $entity = new Entity('Author');
        $database = new Database();
        $database->addEntity($entity);
        $this->assertCount(1, $database->getEntities(), 'First call adds the entity');
        $database->addEntity($entity);
        $this->assertCount(1, $database->getEntities(), 'Second call does nothing');
    }

    public function testGetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = $this->getSchemaMock('bookstore', [
            'generator_config' => $config
        ]);

        $database = new Database();
        $database->setSchema($schema);

        $this->assertInstanceOf('Propel\Generator\Config\GeneratorConfig', $database->getGeneratorConfig());
        $this->assertSame($config, $database->getGeneratorConfig());
    }

    public function testAddDomain()
    {
        $domain1 = $this->getDomainMock('foo');
        $domain2 = $this->getDomainMock('bar');

        $database = new Database();
        $database->addDomain($domain1);
        $database->addDomain($domain2);

        $this->assertSame($domain1, $database->getDomain('foo'));
        $this->assertSame($domain2, $database->getDomain('bar'));
        $this->assertNull($database->getDomain('baz'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidDefaultStringFormat()
    {
        $database = new Database();
        $database->setStringFormat('FOO');
    }

    /**
     * @dataProvider provideSupportedFormats
     *
     */
    public function testSetDefaultStringFormat($format)
    {
        $database = new Database();
        $database->setStringFormat($format);

        $this->assertSame(strtoupper($format), $database->getStringFormat());
    }

    public function provideSupportedFormats()
    {
        return [
            ['xml'],
            ['yaml'],
            ['json'],
            ['csv'],
        ];
    }

    public function testSetHeavyIndexing()
    {
        $database = new Database();
        $database->setHeavyIndexing(true);

        $this->assertTrue($database->isHeavyIndexing());
    }

    public function testSetDefaultIdMethod()
    {
        $database = new Database();
        $database->setIdMethod('native');

        $this->assertSame('native', $database->getIdMethod());
    }

    public function testAddEntityWithSameNameOnDifferentSchema()
    {
        $db = new Database();
        $db->setPlatform(new PgsqlPlatform());

        $t1 = new Entity('t1');
        $db->addEntity($t1);
        $this->assertEquals('t1', $t1->getName());

        $t1b = new Entity('t1');
        $t1b->setSchemaName('bis');
        $db->addEntity($t1b);
        $this->assertNotSame($t1b, $db->getEntityByName('t1'), 'Entities with same name are not added to the database');
    }
}
