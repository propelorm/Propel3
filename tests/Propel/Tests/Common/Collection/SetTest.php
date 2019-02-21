<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

declare(strict_types=1);

namespace Propel\Tests\Common\Collection;

use Propel\Common\Collection\Set;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Vendor;
use Propel\Tests\TestCase;

class SetTest extends TestCase
{
    public function testAdd()
    {
        $object = new Domain();
        $object->setName('myDomain');
        $set = new Set([$object], Domain::class);

        $this->assertTrue($set->search($object, function($element, $query){
            return $element === $query;
        }));

        $object1 = new Domain();
        $object1->setName('mySecondDomain');

        $set->add($object1);

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
        $set = new Set([$obj], Domain::class);
        $wrong = new Vendor();
        $set->add($wrong);
    }
}
