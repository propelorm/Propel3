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

namespace Propel\Tests\Generator\Manager;

use Propel\Generator\Manager\BehaviorManager;
use Propel\Tests\TestCase;
use Propel\Generator\Config\QuickGeneratorConfig;

/**
 * Tests the table structure behavior hooks.
 *
 * @author Thomas Gossmann
 */
class BehaviorManagerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        require_once(__DIR__ . '/../../Fixtures/behavior-installer/src/gossi/propel/behavior/l10n/L10nBehavior.php');
        require_once(__DIR__ . '/../../Fixtures/behavior-development/src/CollectionBehavior.php');
    }

    public function testBehaviorManagerWithComposerLock()
    {
        $configOptions['propel']['paths']['composerDir'] = __DIR__ . '/../../Fixtures/behavior-installer';
        $config = new QuickGeneratorConfig($configOptions);
        $manager = new BehaviorManager($config);
        
        // test found behaviors
        $behaviors = $manager->getBehaviors();
        $this->assertSame(1, count($behaviors));
        
        $this->assertTrue(array_key_exists('l10n', $behaviors));
        $this->assertSame('gossi/propel-l10n-behavior', $behaviors['l10n']['package']);
        
        // test class name
        $this->assertSame('\\gossi\\propel\\behavior\\l10n\\L10nBehavior', $manager->getClassname('l10n'));
    }
    
    public function testBehaviorManagerWithComposerJson()
    {
        $configOptions['propel']['paths']['composerDir'] = __DIR__ . '/../../Fixtures/behavior-development';
        $config = new QuickGeneratorConfig($configOptions);
        $manager = new BehaviorManager($config);
    
        // test found behaviors
        $behaviors = $manager->getBehaviors();
        $this->assertSame(1, count($behaviors));
    
        $this->assertTrue(array_key_exists('collection', $behaviors));
        $this->assertSame('propel/collection-behavior', $behaviors['collection']['package']);
    
        // test class name
        $this->assertSame('\\Propel\\Behavior\\Collection\\CollectionBehavior', $manager->getClassname('collection'));
    }
}
