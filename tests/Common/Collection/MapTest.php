<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Common\Collection;

use Propel\Common\Collection\Map;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Vendor;
use Propel\Tests\TestCase;

class MapTest extends TestCase
{
    public function testAdd()
    {
        $object = new Domain();
        $object->setName('myDomain');
        $set = new Map(['obj' => $object], Domain::class);

        $this->assertTrue($set->search($object, function($element, $query){
            return $element === $query;
        }));

        $object1 = new Domain();
        $object1->setName('mySecondDomain');

        $set->set('obj1', $object1);

        $this->assertEquals(2, $set->size());
        $this->assertTrue($set->search($object1, function($element, $query){
            return $element === $query;
        }));
    }

    /**
     * @expectedException \Propel\Common\Collection\Exception\CollectionException
     */
    public function testAddWrongObjectThrowsException()
    {
        $obj = new Domain();
        $set = new Map(['obj' => $obj], Domain::class);
        $wrong = new Vendor();
        $set->set('wrong', $wrong);
    }
}
