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

use Propel\Generator\Model\Domain;

/**
 * Unit test suite for the Domain model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DomainTest extends ModelTestCase
{
    public function testCreateNewDomain()
    {
        $domain = new Domain('FLOAT', 'DOUBLE', 10, 2);

        $this->assertSame('FLOAT', $domain->getType());
        $this->assertSame('DOUBLE', $domain->getSqlType());
        $this->assertSame(10, $domain->getSize());
        $this->assertSame(2, $domain->getScale());
    }

    public function testSetDatabase()
    {
        $domain = new Domain();
        $domain->setDatabase($this->getDatabaseMock('bookstore'));

        $this->assertInstanceOf('Propel\Generator\Model\Database', $domain->getDatabase());
    }

    public function testReplaceMappingAndSqlTypes()
    {
        $value = $this->getFieldDefaultValueMock();

        $domain = new Domain('FLOAT', 'DOUBLE');
        $domain->setType('BOOLEAN');
        $domain->replaceSqlType('INT');
        $domain->setDefaultValue($value);

        $this->assertSame('BOOLEAN', $domain->getType());
        $this->assertSame('INT', $domain->getSqlType());
        $this->assertInstanceOf('Propel\Generator\Model\FieldDefaultValue', $value);
    }

    public function testGetNoPhpDefaultValue()
    {
        $domain = new Domain();

        $this->assertNull($domain->getPhpDefaultValue());
    }

    public function testGetPhpDefaultValue()
    {
        $value = $this->getFieldDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('foo'))
        ;

        $domain = new Domain('VARCHAR');
        $domain->setDefaultValue($value);

        $this->assertSame('foo', $domain->getPhpDefaultValue());
    }

    /**
     * @dataProvider provideBooleanValues
     *
     */
    public function testGetBooleanValue($mappingType, $booleanAsString, $expected)
    {
        $value = $this->getFieldDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($booleanAsString))
        ;

        $domain = new Domain($mappingType);
        $domain->setDefaultValue($value);

        $this->assertSame($expected, $domain->getPhpDefaultValue());
    }

    public function provideBooleanValues()
    {
        return [
            ['BOOLEAN', 1, true],
            ['BOOLEAN', 0, false],
            ['BOOLEAN', 't', true],
            ['BOOLEAN', 'f', false],
            ['BOOLEAN', 'y', true],
            ['BOOLEAN', 'n', false],
            ['BOOLEAN', 'yes', true],
            ['BOOLEAN', 'no', false],
            ['BOOLEAN', 'true', true],
            ['BOOLEAN_EMU', 'true', true],
            ['BOOLEAN', 'false', false],
            ['BOOLEAN_EMU', 'false', false],
        ];
    }

    public function testGetPhpDefaultValueArray()
    {
        $value = $this->getFieldDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('foo, bar, baz, foobar'))
        ;

        $domain = new Domain('ARRAY');
        $domain->setDefaultValue($value);

        $this->assertSame('||foo | bar | baz | foobar||', $domain->getPhpDefaultValue());
    }

    public function testGetPhpDefaultValueArrayNull()
    {
        $value = $this->getFieldDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(''))
        ;

        $domain = new Domain('ARRAY');
        $domain->setDefaultValue($value);

        $this->assertNull($domain->getPhpDefaultValue());
    }

    public function testGetPhpDefaultValueArrayDelimiter()
    {
        $value = $this->getFieldDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(' | '))
        ;

        $domain = new Domain('ARRAY');
        $domain->setDefaultValue($value);

        $this->assertNull($domain->getPhpDefaultValue());
    }

    public function testCantGetPhpDefaultValue()
    {
        $value = $this->getFieldDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('isExpression')
            ->will($this->returnValue(true))
        ;

        $domain = new Domain();
        $domain->setDefaultValue($value);

        $this->expectException('Propel\Generator\Exception\EngineException');
        $domain->getPhpDefaultValue();
    }

    /**
     * @dataProvider provideSizeDefinitions
     *
     */
    public function testGetSizeDefinition($size, $scale, $definition)
    {
        $domain = new Domain('FLOAT', 'DOUBLE', $size, $scale);

        $this->assertSame($definition, $domain->getSizeDefinition());
    }

    public function provideSizeDefinitions()
    {
        return [
            [10, null, '(10)'],
            [10, 2, '(10,2)'],
            [null, null, ''],
        ];
    }

    public function testCopyDomain()
    {
        $value = $this->getFieldDefaultValueMock();

        $domain = new Domain();
        $domain->setType('FLOAT');
        $domain->setSqlType('DOUBLE');
        $domain->setSize(10);
        $domain->setScale(2);
        $domain->setName('Mapping between FLOAT and DOUBLE');
        $domain->setDescription('Some description');
        $domain->setDefaultValue($value);

        $newDomain = new Domain();
        $newDomain->copy($domain);

        $this->assertSame('FLOAT', $newDomain->getType());
        $this->assertSame('DOUBLE', $newDomain->getSqlType());
        $this->assertSame(10, $newDomain->getSize());
        $this->assertSame(2, $newDomain->getScale());
        $this->assertSame('Mapping between FLOAT and DOUBLE', $newDomain->getName());
        $this->assertSame('Some description', $newDomain->getDescription());
        $this->assertInstanceOf('Propel\Generator\Model\FieldDefaultValue', $value);
    }

    public function testCloneWithDefaultValue()
    {
        $value = $this->getFieldDefaultValueMock();

        $domain = new Domain();
        $domain->setDefaultValue($value);

        $clonedDoman = clone $domain;

        $this->assertEquals($domain, $clonedDoman);
        $this->assertNotSame($domain, $clonedDoman);
    }

    public function testCloneWithoutDefaultValue()
    {
        $domain = new Domain();
        $clonedDoman = clone $domain;

        $this->assertEquals($domain, $clonedDoman);
        $this->assertNotSame($domain, $clonedDoman);
    }

    private function getFieldDefaultValueMock()
    {
        $value = $this
            ->getMockBuilder('Propel\Generator\Model\FieldDefaultValue')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return $value;
    }
}
