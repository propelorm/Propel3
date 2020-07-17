<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\FieldDefaultValue;
use \Propel\Tests\TestCase;

/**
 * Tests for FieldDefaultValue class.
 *
 */
class FieldDefaultValueTest extends TestCase
{
    public function equalsProvider(): array
    {
        return [
            [new FieldDefaultValue('foo', 'bar'), new FieldDefaultValue('foo', 'bar'), true],
            [new FieldDefaultValue('foo', 'bar'), new FieldDefaultValue('foo1', 'bar'), false],
            [new FieldDefaultValue('foo', 'bar'), new FieldDefaultValue('foo', 'bar1'), false],
            [new FieldDefaultValue('current_timestamp', 'bar'), new FieldDefaultValue('now()', 'bar'), true],
            [new FieldDefaultValue('current_timestamp', 'bar'), new FieldDefaultValue('now()', 'bar1'), false],
        ];
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals($def1, $def2, $test): void
    {
        if ($test) {
            $this->assertTrue($def1->equals($def2));
        } else {
            $this->assertFalse($def1->equals($def2));
        }
    }

    public function testIsExpression(): void
    {
        $default = new FieldDefaultValue('SUM', FieldDefaultValue::TYPE_EXPR);
        $this->assertTrue($default->isExpression());
    }
}
