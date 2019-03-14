<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class GeneratedPKLessQueryBuilderTest extends TestCase
{

    /**
     * @expectedException           \Propel\Runtime\Exception\PropelException
     * @expectedExceptionMessage    does not have a primary key.
     */
    public function testThrowsAnError()
    {
        $schema = <<<SCHEMA
<database name="primarykey_less_test">
    <entity name="stuff">
        <field name="key" type="VARCHAR" />
        <field name="value" type="VARCHAR" />
    </entity>
</database>
SCHEMA;

        QuickBuilder::buildSchema($schema);
    }
}
