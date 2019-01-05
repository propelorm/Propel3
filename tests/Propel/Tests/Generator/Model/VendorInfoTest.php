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

use Propel\Generator\Model\Vendor;
use Propel\Tests\TestCase;

/**
 * Unit test suite for the VendorInfo model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class VendorInfoTest extends TestCase
{
    public function testSetupObject()
    {
        $info = new Vendor();
        $info->setType('foo');

        $this->assertSame('foo', $info->getType());
    }

    public function testSetUpObjectWithParameters()
    {
        $info = new Vendor('foo', ['bar' => 'baz']);

        $this->assertSame('foo', $info->getType());
        $this->assertTrue($info->hasParameter('bar'));
        $this->assertEquals('baz', $info->getParameter('bar'));
    }

    public function testGetSetType()
    {
        $info = new Vendor('foo');

        $this->assertSame('foo', $info->getType());
        $this->assertTrue($info->isEmpty());
    }

    public function testSetParameter()
    {
        $info = new Vendor();
        $info->setParameter('foo', 'bar');

        $this->assertFalse($info->isEmpty());
        $this->assertTrue($info->hasParameter('foo'));
        $this->assertSame('bar', $info->getParameter('foo'));
    }

    public function testSetParameters()
    {
        $info = new Vendor();
        $info->setParameters(['foo' => 'bar', 'baz' => 'bat']);

        $this->assertFalse($info->isEmpty());
        $this->assertArrayHasKey('foo', $info->getParameters());
        $this->assertArrayHasKey('baz', $info->getParameters());
    }

    public function testMergeVendorInfo()
    {
        $current = new Vendor('mysql');
        $current->setParameters(['foo' => 'bar', 'baz' => 'bat']);

        $toMerge = new Vendor('mysql');
        $toMerge->setParameters(['foo' => 'wat', 'int' => 'mix']);

        $merged = $current->getMergedVendorInfo($toMerge);

        $this->assertInstanceOf('Propel\Generator\Model\Vendor', $merged);

        $this->assertSame('wat', $merged->getParameter('foo'));
        $this->assertSame('bat', $merged->getParameter('baz'));
        $this->assertSame('mix', $merged->getParameter('int'));
    }
}
