<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Inheritance;
use Propel\Tests\TestCase;

/**
 * Unit test suite for the Inheritance model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class InheritanceTest extends TestCase
{
    public function testCreateNewInheritance()
    {
        $field = $this
            ->getMockBuilder('Propel\Generator\Model\Field')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $inheritance = new Inheritance();
        $inheritance->setAncestor('BaseObject');
        $inheritance->setKey('baz');
        $inheritance->setClassName('Foo\Bar');
        $inheritance->setField($field);

        $this->assertInstanceOf('Propel\Generator\Model\Field', $inheritance->getField());
        $this->assertSame('BaseObject', $inheritance->getAncestor());
        $this->assertSame('baz', $inheritance->getKey());
        $this->assertSame('Foo\Bar', $inheritance->getClassName());
    }
}
