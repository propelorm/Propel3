<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Configuration;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\RelationMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for PHP5TableMapBuilder.
 *
 * @author François Zaninotto
 */
class GeneratedRelationMapTest extends TestCaseFixtures
{
    /**
     * @var DatabaseMap
     */
    protected $databaseMap;

    protected function setUp()
    {
        parent::setUp();
        $this->databaseMap = Configuration::getCurrentConfiguration()->getDatabase('bookstore');
    }

    public function testGetRightTable()
    {
        $bookTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Book');
        $authorTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Author');
        $this->assertEquals($authorTable, $bookTable->getRelation('author')->getRightEntity(), 'getRightEntity() returns correct table when called on a many to one relationship');
        $this->assertEquals($bookTable, $authorTable->getRelation('books')->getRightEntity(), 'getRightEntity() returns correct table when called on a one to many relationship');
        $bookEmpTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\BookstoreEmployee');
        $bookEmpAccTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\BookstoreEmployeeAccount');
        $this->assertEquals($bookEmpAccTable, $bookEmpTable->getRelation('bookstoreEmployeeAccounts')->getRightEntity(), 'getRightEntity() returns correct table when called on a one to one relationship');
        $this->assertEquals($bookEmpTable, $bookEmpAccTable->getRelation('employee')->getRightEntity(), 'getRightEntity() returns correct table when called on a one to one relationship');
    }

    public function testColumnMappings()
    {
        $bookTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Book');
        $this->assertEquals(['Propel\Tests\Bookstore\Book.authorId' => 'Propel\Tests\Bookstore\Author.id'], $bookTable->getRelation('author')->getFieldMappings(), 'getFieldMappings returns local to foreign by default');
        $this->assertEquals(['Propel\Tests\Bookstore\Book.authorId' => 'Propel\Tests\Bookstore\Author.id'], $bookTable->getRelation('author')->getFieldMappings(RelationMap::LEFT_TO_RIGHT), 'getFieldMappings returns local to foreign when asked left to right for a many to one relationship');

        $authorTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Author');
        $this->assertEquals(['Propel\Tests\Bookstore\Book.authorId' => 'Propel\Tests\Bookstore\Author.id'], $authorTable->getRelation('books')->getFieldMappings(), 'getFieldMappings returns local to foreign by default');
        $this->assertEquals(['Propel\Tests\Bookstore\Author.id' => 'Propel\Tests\Bookstore\Book.authorId'], $authorTable->getRelation('books')->getFieldMappings(RelationMap::LEFT_TO_RIGHT), 'getFieldMappings returns foreign to local when asked left to right for a one to many relationship');

        $bookEmpTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\BookstoreEmployee');
        $this->assertEquals(['Propel\Tests\Bookstore\BookstoreEmployeeAccount.employeeId' => 'Propel\Tests\Bookstore\BookstoreEmployee.id'], $bookEmpTable->getRelation('bookstoreEmployeeAccounts')->getFieldMappings(), 'getFieldMappings returns local to foreign by default');
        $this->assertEquals(['Propel\Tests\Bookstore\BookstoreEmployee.id' => 'Propel\Tests\Bookstore\BookstoreEmployeeAccount.employeeId'], $bookEmpTable->getRelation('bookstoreEmployeeAccounts')->getFieldMappings(RelationMap::LEFT_TO_RIGHT), 'getFieldMappings returns foreign to local when asked left to right for a one to one relationship');
    }

    public function testCountColumnMappings()
    {
        $bookTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Book');
        $this->assertEquals(1, $bookTable->getRelation('author')->countFieldMappings());

        $rfTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\ReaderFavorite');
        $this->assertEquals(2, $rfTable->getRelation('bookOpinion')->countFieldMappings());
    }

    public function testIsComposite()
    {
        $bookTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Book');
        $this->assertFalse($bookTable->getRelation('author')->isComposite());

        $rfTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\ReaderFavorite');
        $this->assertTrue($rfTable->getRelation('bookOpinion')->isComposite());
    }
}
