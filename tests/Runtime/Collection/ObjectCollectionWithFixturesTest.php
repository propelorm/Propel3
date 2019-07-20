<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\collection;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\PropelQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Test class for ObjectCollection.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ObjectCollectionWithFixturesTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate($this->con);
    }

    public function testSave()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        foreach ($books as $book) {
            $book->setTitle('foo');
        }
        $books->save();
        // check that all the books are saved
        foreach ($books as $book) {
            $this->assertFalse($book->isModified());
        }
        // check that the modifications are persisted
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        foreach ($books as $book) {
            $this->assertEquals('foo', $book->getTitle('foo'));
        }
    }

    public function testDelete()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $books->delete();
        // check that all the books are deleted
        foreach ($books as $book) {
            $this->assertTrue($book->isDeleted());
        }
        // check that the modifications are persisted
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $this->assertEquals(0, count($books));
    }

    public function testFromArray()
    {
        $author = new Author();
        $author->setFirstName('Jane');
        $author->setLastName('Austen');
        $author->save();
        $books = [
            ['Title' => 'Mansfield Park', 'ISBN' => 'FA404', 'AuthorId' => $author->getId()],
            ['Title' => 'Pride And Prejudice', 'ISBN' => 'FA404', 'AuthorId' => $author->getId()]
        ];
        $col = new ObjectCollection();
        $col->setModel('Propel\Tests\Bookstore\Book');
        $col->fromArray($books);
        $col->save();

        $nbBooks = PropelQuery::from('Propel\Tests\Bookstore\Book')->count();
        $this->assertEquals(6, $nbBooks);

        $booksByJane = PropelQuery::from('Propel\Tests\Bookstore\Book b')
            ->join('b.Author a')
            ->where('a.LastName = ?', 'Austen')
            ->count();
        $this->assertEquals(2, $booksByJane);
    }

    public function testToArray()
    {
        BookTableMap::clearInstancePool();
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $booksArray = $books->toArray();
        $this->assertEquals(4, count($booksArray));

        foreach ($booksArray as $key => $book) {
            $this->assertEquals($books[$key]->toArray(), $book);
        }

        $booksArray = $books->toArray();
        $keys = [0, 1, 2, 3];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray(null, true);
        $keys = [
            'Book_0',
            'Book_1',
            'Book_2',
            'Book_3'
        ];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray('Title');
        $keys = ['Harry Potter and the Order of the Phoenix', 'Quicksilver', 'Don Juan', 'The Tin Drum'];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->toArray('Title', true);
        $keys = [
            'Book_Harry Potter and the Order of the Phoenix',
            'Book_Quicksilver',
            'Book_Don Juan',
            'Book_The Tin Drum'
        ];
        $this->assertEquals($keys, array_keys($booksArray));
    }

    public function testGetArrayCopy()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();
        $booksArray = $books->getArrayCopy();
        $this->assertEquals(4, count($booksArray));

        foreach ($booksArray as $key => $book) {
            $this->assertEquals($books[$key], $book);
        }

        $booksArray = $books->getArrayCopy();
        $keys = [0, 1, 2, 3];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->getArrayCopy(null, true);
        $keys = [
            'Book_0',
            'Book_1',
            'Book_2',
            'Book_3'
        ];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->getArrayCopy('Title');
        $keys = ['Harry Potter and the Order of the Phoenix', 'Quicksilver', 'Don Juan', 'The Tin Drum'];
        $this->assertEquals($keys, array_keys($booksArray));

        $booksArray = $books->getArrayCopy('Title', true);
        $keys = [
            'Book_Harry Potter and the Order of the Phoenix',
            'Book_Quicksilver',
            'Book_Don Juan',
            'Book_The Tin Drum'
        ];
        $this->assertEquals($keys, array_keys($booksArray));
    }

    public function testToKeyValue()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();

        $expected = [];
        foreach ($books as $book) {
            $expected[$book->getTitle()] = $book->getISBN();
        }
        $booksArray = $books->toKeyValue('Title', 'ISBN');
        $this->assertEquals(4, count($booksArray));
        $this->assertEquals($expected, $booksArray, 'toKeyValue() turns the collection to an associative array');

        $expected = [];
        foreach ($books as $book) {
            $expected[$book->getISBN()] = $book->getTitle();
        }
        $booksArray = $books->toKeyValue('ISBN');
        $this->assertEquals($expected, $booksArray, 'toKeyValue() uses __toString() for the value if no second field name is passed');

        $expected = [];
        foreach ($books as $book) {
            $expected[$book->getId()] = $book->getTitle();
        }
        $booksArray = $books->toKeyValue();
        $this->assertEquals($expected, $booksArray, 'toKeyValue() uses primary key for the key and __toString() for the value if no field name is passed');
    }

    public function testToKeyIndex()
    {
        $books = PropelQuery::from('Propel\Tests\Bookstore\Book')->find();

        $expected = [];
        foreach ($books as $book) {
            $expected[$book->getTitle()] = $book;
        }
        $booksArray = $books->toKeyIndex('Title');
        $this->assertEquals(4, count($booksArray));
        $this->assertEquals($expected, $booksArray, 'toKeyIndex() turns the collection to `Title` indexed array');

        $this->assertEquals($booksArray, $books->toKeyIndex('title'));

        $expected = [];
        foreach ($books as $book) {
            $expected[$book->getISBN()] = $book;
        }
        $this->assertEquals(4, count($booksArray));
        $booksArray = $books->toKeyIndex('ISBN');
        $this->assertEquals($expected, $booksArray, 'toKeyIndex() uses `ISBN` for the key');

        $expected = [];
        foreach ($books as $book) {
            $expected[$book->getId()] = $book;
        }
        $this->assertEquals(4, count($booksArray));
        $booksArray = $books->toKeyIndex();
        $this->assertEquals($expected, $booksArray, 'toKeyIndex() uses primary key for the key');
    }
}
