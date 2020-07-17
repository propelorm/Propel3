<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Delegate;

use \Map\DelegateDelegateEntityMap;
use Propel\Generator\Behavior\Delegate\DelegateBehavior;
use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Configuration;
use Propel\Tests\TestCase;

/**
 * Tests for DelegateBehavior class
 *
 * @author François Zaninotto
 */
class DelegateBehaviorTest extends TestCase
{
    public function setUp(): void
    {
        if (!class_exists('\DelegateDelegate')) {
            $schema = <<<EOF
<database name="delegate_behavior_test_1" activeRecord="true">

    <entity name="DelegateMain">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="delegateId" type="INTEGER" />
        <relation target="SecondDelegateDelegate">
            <reference local="delegateId" foreign="id" />
        </relation>
        <behavior name="delegate">
            <parameter name="to" value="DelegateDelegate, SecondDelegateDelegate" />
        </behavior>
    </entity>

    <entity name="DelegateDelegate">
        <field name="subtitle" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="SecondDelegateDelegate">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="summary" type="VARCHAR" size="100" primaryString="true" />
        <behavior name="delegate">
            <parameter name="to" value="ThirdDelegateDelegate" />
        </behavior>
    </entity>

    <entity name="ThirdDelegateDelegate">
        <field name="body" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="DelegatePlayer">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="firstName" type="VARCHAR" size="100" primaryString="true" />
        <field name="lastName" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="DelegateBasketballer">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="points" type="INTEGER" />
        <field name="fieldGoals" type="INTEGER" />
        <field name="playerId" type="INTEGER" />
        <relation target="DelegatePlayer">
            <reference local="playerId" foreign="id" />
        </relation>
        <behavior name="delegate">
            <parameter name="to" value="DelegatePlayer" />
        </behavior>
    </entity>

    <entity name="DelegateTeam">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="name" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="DelegateFootballer">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="goalsScored" type="INTEGER" />
        <field name="foulsCommitted" type="INTEGER" />
        <field name="playerId" type="INTEGER" />
        <relation target="DelegatePlayer">
            <reference local="playerId" foreign="id" />
        </relation>
        <field name="teamId" type="INTEGER" />
        <relation target="DelegateTeam">
            <reference local="teamId" foreign="id" />
        </relation>
        <behavior name="delegate">
            <parameter name="to" value="DelegatePlayer, DelegateTeam" />
        </behavior>
    </entity>

</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testModifyTableRelatesOneToOneDelegate()
    {
        $delegateTable = QuickBuilder::$configuration->getEntityMap(DelegateDelegateEntityMap::ENTITY_CLASS);
        $this->assertEquals(2, count($delegateTable->getFields()));
        $this->assertEquals(1, count($delegateTable->getRelations()));
        $this->assertTrue(method_exists('DelegateMain', 'getDelegateDelegate'));
        $this->assertTrue(method_exists('DelegateDelegate', 'getDelegateMain'));
    }

    public function testOneToOneDelegationCreatesANewDelegateIfNoneExists()
    {
        $main = new \DelegateMain();
        $main->setSubtitle('foo');
        $delegate = $main->getDelegateDelegate();
        $this->assertInstanceOf('DelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('foo', $delegate->getSubtitle());
        $this->assertEquals('foo', $main->getSubtitle());
    }

    public function testManyToOneDelegationCreatesANewDelegateIfNoneExists()
    {
        $main = new \DelegateMain();
        $main->setSummary('foo');
        $delegate = $main->getSecondDelegateDelegate();
        $this->assertInstanceOf('SecondDelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('foo', $delegate->getSummary());
        $this->assertEquals('foo', $main->getSummary());
    }

    public function testOneToOneDelegationUsesExistingDelegateIfExists()
    {
        $main = new \DelegateMain();
        $delegate = new \DelegateDelegate();
        $delegate->setSubtitle('bar');
        $main->setDelegateDelegate($delegate);
        $this->assertEquals('bar', $main->getSubtitle());
    }

    public function testManyToOneDelegationUsesExistingDelegateIfExists()
    {
        $main = new \DelegateMain();
        $delegate = new \SecondDelegateDelegate();
        $delegate->setSummary('bar');
        $main->setSecondDelegateDelegate($delegate);
        $this->assertEquals('bar', $main->getSummary());
    }

    public function testAModelCanHaveSeveralDelegates()
    {
        $main = new \DelegateMain();
        $main->setSubtitle('foo');
        $main->setSummary('bar');
        $delegate = $main->getDelegateDelegate();
        $this->assertInstanceOf('DelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('foo', $delegate->getSubtitle());
        $this->assertEquals('foo', $main->getSubtitle());
        $delegate = $main->getSecondDelegateDelegate();
        $this->assertInstanceOf('SecondDelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('bar', $delegate->getSummary());
        $this->assertEquals('bar', $main->getSummary());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\BadMethodCallException
     */
    public function testAModelCannotHaveCascadingDelegates()
    {
        $main = new \DelegateMain();
        $main->setSummary('bar');
        $main->setBody('baz');
    }

    /**
     * @group test
     */
    public function testOneToOneDelegatesCanBePersisted()
    {
        $main = new \DelegateMain();
        $main->setSubtitle('foo');
        $main->save();
        $this->assertFalse($main->isNew());
        $this->assertFalse($main->getDelegateDelegate()->isNew());
        $this->assertNull($main->getSecondDelegateDelegate());
    }

    public function testManyToOneDelegatesCanBePersisted()
    {
        $main = new \DelegateMain();
        $main->setSummary('foo');
        $main->save();
        $this->assertFalse($main->isNew());
        $this->assertFalse($main->getSecondDelegateDelegate()->isNew());
        $this->assertNull($main->getDelegateDelegate());
    }

    public function testDelegateSimulatesClassTableInheritance()
    {
        $basketballer = new \DelegateBasketballer();
        $basketballer->setPoints(101);
        $basketballer->setFieldGoals(47);
        $this->assertNull($basketballer->getDelegatePlayer());
        $basketballer->setFirstName('Michael');
        $basketballer->setLastName('Giordano');
        $this->assertNotNull($basketballer->getDelegatePlayer());
        $this->assertEquals('Michael', $basketballer->getDelegatePlayer()->getFirstName());
        $this->assertEquals('Michael', $basketballer->getFirstName());
        $basketballer->save(); // should not throw exception
    }

    public function testDelegateSimulatesMultipleClassTableInheritance()
    {
        Configuration::$globalConfiguration->getSession()->reset();
        $footballer = new \DelegateFootballer();

        $footballer->setGoalsScored(43);
        $footballer->setFoulsCommitted(4);
        $this->assertNull($footballer->getDelegatePlayer());
        $this->assertNull($footballer->getDelegateTeam());
        $footballer->setFirstName('Michael');
        $footballer->setLastName('Giordano');

        $this->assertNotNull($footballer->getDelegatePlayer());
        $this->assertEquals('Michael', $footballer->getDelegatePlayer()->getFirstName());
        $this->assertEquals('Michael', $footballer->getFirstName());
        $footballer->setName('Saint Etienne');

        $this->assertNotNull($footballer->getDelegateTeam());
        $this->assertEquals('Saint Etienne', $footballer->getDelegateTeam()->getName());
        $this->assertEquals('Saint Etienne', $footballer->getName());
        $footballer->save(); // should not throw exception
    }

//    public function testTablePrefixSameDatabase()
//    {
//        $schema = <<<EOF
//<database name="testTablePrefixSameDatabase_database" tablePrefix="foo">
//
//    <entity name="testTablePrefixSameDatabase_main">
//        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
//        <field name="title" type="VARCHAR" size="100" primaryString="true" />
//        <field name="delegateId" type="INTEGER" />
//        <relation target="testTablePrefixSameDatabase_delegate">
//            <reference local="delegateId" foreign="id" />
//        </relation>
//        <behavior name="delegate">
//            <parameter name="to" value="testTablePrefixSameDatabase_delegate" />
//        </behavior>
//    </entity>
//
//    <entity name="testTablePrefixSameDatabase_delegate">
//        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
//        <field name="subtitle" type="VARCHAR" size="100" primaryString="true" />
//    </entity>
//
//</database>
//EOF;
//        QuickBuilder::buildSchema($schema);
//        $main = new \TestTablePrefixSameDatabaseMain();
//        $main->setSubtitle('bar');
//        $delegate = $main->getTestTablePrefixSameDatabaseDelegate();
//        $this->assertInstanceOf('TestTablePrefixSameDatabaseDelegate', $delegate);
//        $this->assertTrue($delegate->isNew());
//        $this->assertEquals('bar', $delegate->getSubtitle());
//        $this->assertEquals('bar', $main->getSubtitle());
//    }
}
