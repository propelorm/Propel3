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
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for RelatedMap::getSymmetricalRelation.
 *
 * @author François Zaninotto
 */
class RelatedMapSymmetricalTest extends TestCaseFixtures
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

    public function testOneToMany()
    {
        $bookTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Book');
        $bookToAuthor = $bookTable->getRelation('Author');
        $authorTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Author');
        $authorToBook = $authorTable->getRelation('books');
        $this->assertEquals($authorToBook, $bookToAuthor->getSymmetricalRelation());
        $this->assertEquals($bookToAuthor, $authorToBook->getSymmetricalRelation());
    }

    public function testOneToOne()
    {
        $accountTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\BookstoreEmployeeAccount');
        $accountToEmployee = $accountTable->getRelation('employee');
        $employeeTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\BookstoreEmployee');
        $employeeToAccount = $employeeTable->getRelation('bookstoreEmployeeAccounts');
        $this->assertEquals($accountToEmployee, $employeeToAccount->getSymmetricalRelation());
        $this->assertEquals($employeeToAccount, $accountToEmployee->getSymmetricalRelation());
    }

    public function testSeveralRelationsOnSameTable()
    {
        $authorTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Author');
        $authorToEssay = $authorTable->getRelation('essaysByFirstAuthor');
        $essayTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\Essay');
        $essayToAuthor = $essayTable->getRelation('firstAuthor');
        $this->assertEquals($authorToEssay, $essayToAuthor->getSymmetricalRelation());
        $this->assertEquals($essayToAuthor, $authorToEssay->getSymmetricalRelation());
    }

    public function testCompositeForeignKey()
    {
        $favoriteTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\ReaderFavorite');
        $favoriteToOpinion = $favoriteTable->getRelation('bookOpinion');
        $opinionTable = $this->databaseMap->getEntity('Propel\Tests\Bookstore\BookOpinion');
        $opinionToFavorite = $opinionTable->getRelation('readerFavorites');
        $this->assertEquals($favoriteToOpinion, $opinionToFavorite->getSymmetricalRelation());
        $this->assertEquals($opinionToFavorite, $favoriteToOpinion->getSymmetricalRelation());
    }

}
