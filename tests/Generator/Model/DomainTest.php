<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Domain;
use Propel\Generator\Model\FieldDefaultValue;
use function DeepCopy\deep_copy;

/**
 * Unit test suite for the Domain model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DomainTest extends ModelTestCase
{
    public function testCreateNewDomain(): void
    {
        $domain = new Domain('FLOAT', 'DOUBLE', 10, 2);

        $this->assertSame('FLOAT', $domain->getType());
        $this->assertSame('DOUBLE', $domain->getSqlType());
        $this->assertSame(10, $domain->getSize());
        $this->assertSame(2, $domain->getScale());
    }

    public function testSetDatabase(): void
    {
        $domain = new Domain();
        $domain->setDatabase($this->getDatabaseMock('bookstore'));

        $this->assertInstanceOf('Propel\Generator\Model\Database', $domain->getDatabase());
    }

    public function testReplaceMappingAndSqlTypes(): void
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

    public function testGetNoPhpDefaultValue(): void
    {
        $domain = new Domain();

        $this->assertNull($domain->getPhpDefaultValue());
    }

    public function testGetPhpDefaultValue(): void
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
    public function testGetBooleanValue($mappingType, $booleanAsString, $expected): void
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

    public function provideBooleanValues(): array
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

    public function testGetPhpDefaultValueArray(): void
    {
        $value = $this->getFieldDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('foo, bar, baz, foobar'))
        ;

        $domain = new Domain('ARRAY');
        $domain->setDefaultValue($value);

        $this->assertSame('||foo | bar | baz | foobar||', $domain->getPhpDefaultValue()->toString());
    }

    public function testGetPhpDefaultValueArrayNull(): void
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

    public function testGetPhpDefaultValueArrayDelimiter(): void
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

    public function testCantGetPhpDefaultValue(): void
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
    public function testGetSizeDefinition($size, $scale, $definition): void
    {
        $domain = new Domain('FLOAT', 'DOUBLE', $size, $scale);

        $this->assertSame($definition, $domain->getSizeDefinition());
    }

    public function provideSizeDefinitions(): array
    {
        return [
            [10, null, '(10)'],
            [10, 2, '(10,2)'],
            [null, null, ''],
        ];
    }

    public function testCopyDomain(): void
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
        $this->assertSame('Mapping between FLOAT and DOUBLE', $newDomain->getName()->toString());
        $this->assertSame('Some description', $newDomain->getDescription());
        $this->assertInstanceOf('Propel\Generator\Model\FieldDefaultValue', $value);
    }

    public function testCloneWithDefaultValue()
    {
        //Clone not supported. Use myclabs/deep-copy instead
        $value = $this->getFieldDefaultValueMock();

        $domain = new Domain();
        $domain->setDefaultValue($value);

        $clonedDomain = deep_copy($domain);

        $this->assertEquals($domain, $clonedDomain);
        $this->assertNotSame($domain, $clonedDomain);
    }

    public function testCloneWithoutDefaultValue(): void
    {
        //Clone not supported. Use myclabs/deep-copy instead
        $domain = new Domain();
        $clonedDomain = deep_copy($domain);

        $this->assertEquals($domain, $clonedDomain);
        $this->assertNotSame($domain, $clonedDomain);
    }

    private function getFieldDefaultValueMock(): FieldDefaultValue
    {
        $value = $this
            ->getMockBuilder('Propel\Generator\Model\FieldDefaultValue')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return $value;
    }
}
