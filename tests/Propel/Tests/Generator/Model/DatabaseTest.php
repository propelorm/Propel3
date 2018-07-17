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

//     public function testSetupObject()
//     {
//         $database = new Database();
//         $database->loadMapping(array(
//             'name'                   => 'bookstore',
//             'defaultIdMethod'        => 'native',
//             'heavyIndexing'          => 'true',
//             'tablePrefix'            => 'acme_',
//             'defaultStringFormat'    => 'XML',
//         ));

//         $this->assertSame('bookstore', $database->getName());
//         $this->assertSame('XML', $database->getDefaultStringFormat());
//         $this->assertSame('native', $database->getDefaultIdMethod());
//         $this->assertSame('acme_', $database->getTablePrefix());
//         $this->assertTrue($database->isHeavyIndexing());
//         $this->assertTrue($database->getHeavyIndexing());
//     }

//     public function testDoFinalization()
//     {
//         $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
//                             ->disableOriginalConstructor()->getMock();

//         $schema = $this->getSchemaMock('bookstore', array(
//             'generator_config' => $config
//         ));

//         $platform = $this->getPlatformMock();
//         $platform
//             ->expects($this->any())
//             ->method('getMaxFieldNameLength')
//             ->will($this->returnValue(64))
//         ;
//         $platform
//             ->expects($this->any())
//             ->method('getDomainForType')
//             ->with($this->equalTo('TIMESTAMP'))
//             ->will($this->returnValue($this->getDomainMock('TIMESTAMP')))
//         ;

//         $database = new Database();
//         $database->setPlatform($platform);
//         $database->setParentSchema($schema);
//         $database->addEntity($this->getEntityMock('foo'));
//         $database->addEntity($this->getEntityMock('bar'));
//         $database->doFinalInitialization();

//         $this->assertCount(0, $database->getBehaviors());
//         $this->assertSame(2, $database->countEntities());
//     }

    public function testSetParentSchema()
    {
        $schema = $this->getSchemaMock();
        $database = new Database();
        $database->setSchema($schema);

        $this->assertInstanceOf('Propel\Generator\Model\Schema', $database->getSchema());
        $this->assertSame($schema, $database->getSchema());
    }

//     public function testAddBehavior()
//     {
//         $behavior = $this->getBehaviorMock('foo');

//         $database = new Database();

//         $this->assertInstanceOf('Propel\Generator\Model\Behavior', $database->addBehavior($behavior));
//         $this->assertSame($behavior, $database->getBehavior('foo'));
//         $this->assertTrue($database->hasBehavior('foo'));
//     }

//     public function testCantAddInvalidBehavior()
//     {
//         $this->expectException('Propel\Generator\Exception\BehaviorNotFoundException');

//         $database = new Database();
//         $behavior = $database->addBehavior(array('name' => 'foo'));
//     }

//     /**
//      * @dataProvider provideBehaviors
//      *
//      */
//     public function testAddArrayBehavior($name, $class)
//     {
//         $type = sprintf(
//             'Propel\Generator\Behavior\%s\%sBehavior',
//             $class,
//             $class
//         );

//         $database = new Database();
//         $behavior = $database->addBehavior(array('name' => $name));

//         $this->assertInstanceOf($type, $behavior);
//     }

//     public function provideBehaviors()
//     {
//         return array(
//             array('aggregate_field', 'AggregateField'),
//             array('auto_add_pk', 'AutoAddPk'),
//             array('concrete_inheritance', 'ConcreteInheritance'),
//             array('delegate', 'Delegate'),
//             array('nested_set', 'NestedSet'),
//             array('query_cache', 'QueryCache'),
//             array('sluggable', 'Sluggable'),
//             array('sortable', 'Sortable'),
//             array('timestampable', 'Timestampable'),
//         );
//     }

//     public function testGetNextEntityBehavior()
//     {
//         $entity1 = $this->getEntityMock('books', array('behaviors' => array(
//             $this->getBehaviorMock('foo', array(
//                 'is_entity_modified'  => false,
//                 'modification_order' => 2,
//             )),
//             $this->getBehaviorMock('bar', array(
//                 'is_entity_modified'  => false,
//                 'modification_order' => 1,
//             )),
//             $this->getBehaviorMock('baz', array('is_entity_modified'  => true)),
//         )));

//         $entity2 = $this->getEntityMock('authors', array('behaviors' => array(
//             $this->getBehaviorMock('mix', array(
//                 'is_entity_modified'  => false,
//                 'modification_order' => 1,
//             )),
//         )));

//         $database = new Database();
//         $database->addEntity($entity1);
//         $database->addEntity($entity2);

//         $behavior = $database->getNextEntityBehavior();

//         $this->assertInstanceOf('Propel\Generator\Model\Behavior', $behavior);
//         $this->assertSame('bar', $behavior->getName());
//     }

//     public function testCantGetNextEntityBehavior()
//     {
//         $entity1 = $this->getEntityMock('books', array('behaviors' => array(
//             $this->getBehaviorMock('foo', array('is_entity_modified' => true)),
//         )));

//         $database = new Database();
//         $database->addEntity($entity1);

//         $behavior = $database->getNextEntityBehavior();

//         $this->assertNull($database->getNextEntityBehavior());
//     }

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

//     public function testAddArrayEntity()
//     {
//         $database = new Database();
//         $database->addEntity(array('name' => 'books'));
//         $database->addEntity(array('name' => 'authors'));
//         $database->addEntity(array('name' => 'categories', 'skipSql' => 'true'));
//         $database->addEntity(array('name' => 'publishers', 'readOnly' => 'true'));

//         $this->assertTrue($database->hasEntity('books'));
//         $this->assertTrue($database->hasEntity('books', true));
//         $this->assertFalse($database->hasEntity('BOOKS'));
//         $this->assertInstanceOf('Propel\Generator\Model\Entity', $database->getEntity('books'));

//         // 3 entities because read only entity is excluded from the count
//         $this->assertSame(3, $database->countEntities());

//         // 3 entities because skipped sql entity is excluded from the count
//         $this->assertCount(3, $database->getEntitiesForSql());
//     }

//     public function testAddSameEntityTwice()
//     {
//         $this->expectException('Propel\Generator\Exception\EngineException');

//         $database = new Database();
//         $database->addEntity(array('name' => 'authors'));
//         $database->addEntity(array('name' => 'authors'));
//     }

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

//     public function testGetBuildProperty()
//     {
//         $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
//             ->disableOriginalConstructor()->getMock();

//         $config
//             ->expects($this->once())
//             ->method('getConfigProperty')
//             ->with($this->equalTo('generator.database.adapters.mysql.entityType'))
//             ->will($this->returnValue('InnoDB'))
//         ;

//         $schema = $this->getSchemaMock('bookstore', array(
//             'generator_config' => $config
//         ));

//         $database = new Database();
//         $database->setParentSchema($schema);

//         $this->assertSame('InnoDB', $database->getBuildProperty('generator.database.adapters.mysql.entityType'));
//     }

//     public function testAddArrayDomain()
//     {
//         $copiedDomain = $this->getDomainMock('original');

//         $platform = $this->getPlatformMock();
//         $platform
//             ->expects($this->once())
//             ->method('getDomainForType')
//             ->will($this->returnValue($copiedDomain))
//         ;

//         $database = new Database();
//         $database->setPlatform($platform);

//         $domain1  = $database->addDomain(array('name' => 'foo'));

//         $this->assertInstanceOf('Propel\Generator\Model\Domain', $domain1);
//         $this->assertSame($domain1, $database->getDomain('foo'));
//         $this->assertNull($database->getDomain('baz'));
//     }

//     public function testAddDomain()
//     {
//         $domain1 = $this->getDomainMock('foo');
//         $domain2 = $this->getDomainMock('bar');

//         $database = new Database();
//         $database->addDomain($domain1);
//         $database->addDomain($domain2);

//         $this->assertSame($domain1, $database->getDomain('foo'));
//         $this->assertSame($domain2, $database->getDomain('bar'));
//         $this->assertNull($database->getDomain('baz'));
//     }

    public function testSetInvalidDefaultStringFormat()
    {
        $this->expectException('\InvalidArgumentException');

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

//     /**
//      * @expectedException \Propel\Generator\Exception\EngineException
//      * @expectedExceptionMessage Entity "t1" declared twice
//      */
//     public function testAddEntityWithSameNameOnDifferentSchema()
//     {
//         $db = new Database();
//         $db->setPlatform(new PgsqlPlatform());

//         $t1 = new Entity('t1');
//         $db->addEntity($t1);
//         $this->assertEquals('t1', $t1->getName());

//         $t1b = new Entity('t1');
//         $t1b->setSchema('bis');
//         $db->addEntity($t1b);
//         $this->assertEquals('bis.t1', $t1b->getName());
//     }
}
