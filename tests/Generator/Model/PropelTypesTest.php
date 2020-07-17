<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\PropelTypes;
use Propel\Tests\TestCase;

class PropelTypesTest extends TestCase
{
    public function testBooleanValue(): void
    {
        $this->assertTrue(PropelTypes::booleanValue(true));
        $this->assertFalse(PropelTypes::booleanValue(false));
    }

    public function testGetPdoTypeString(): void
    {
        $this->assertSame('\\PDO::PARAM_STR', PropelTypes::getPdoTypeString('VARCHAR'));
        $this->assertEquals('\\PDO::PARAM_INT', PropelTypes::getPdoTypeString('INTEGER'));
    }
}