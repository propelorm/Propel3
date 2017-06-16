<?php

use Propel\Generator\Schema\SchemaReader;
use Propel\Tests\TestCase;

class SchemaReaderTest extends TestCase
{
    public function testBookstore() {
        $reader = new SchemaReader();
        
        $schema = $reader->parse(__DIR__ . '/../../../../Fixtures/bookstore/schema.xml');
        
//         $names = array_map(function(Entity $entity) {
//             return $entity->getName();
//         }, $schema->getDatabase()->getEntities());
        
//         print_r($names);
        
        $this->assertEquals(34, count($schema->getDatabase()->getEntities()));
        
//         $this->assertEquals(34, $schema->getDatabase()->countEntities());
    }
}