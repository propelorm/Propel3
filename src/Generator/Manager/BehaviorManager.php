<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Manager;

use phootwork\collection\Map;
use phootwork\json\Json;
use phootwork\json\JsonException;
use phootwork\lang\Text;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Exception\BehaviorNotFoundException;

/**
 * Behavior manager to locate and instantiate behaviors
 *
 * @author Thomas Gossmann
 *
 */
class BehaviorManager
{
    const BEHAVIOR_PACKAGE_TYPE = 'propel-behavior';
    
    private Map $behaviors; //@todo is this property useful?
    private string $composerDir = '';

    public function __construct(GeneratorConfigInterface $config = null)
    {
        if ($config !== null) {
            $this->composerDir = $config->get('paths.composerDir') ?? '';
        }
        $this->behaviors =new Map();
    }

    /**
     * Instantiates a behavior from a given name
     *
     * @param string $name
     *
     * @return Behavior
     * @throws JsonException
     * @throws BuildException
     */
    public function create(string $name): Behavior
    {
        $class = $this->getClassname($name);
        $behavior = new $class();
        if (!($behavior instanceof Behavior)) {
            throw new BuildException(sprintf(
                'Behavior [%s: %s] not instance of %s',
                $name,
                $class,
                '\Propel\Generator\Model\Behavior'
            ));
        }
        return $behavior;
    }

    /**
     * Searches a composer file
     *
     * @param string $fileName
     *
     * @return SplFileInfo the found composer file or null if composer file isn't found
     */
    private function findComposerFile(string $fileName): ?SplFileInfo
    {
        if ('' !== $this->composerDir) {
            $filePath = "{$this->composerDir}/$fileName";
            
            if (file_exists($filePath)) {
                return new SplFileInfo($filePath, dirname($filePath), dirname($filePath));
            }
        }
        
        $finder = new Finder();
        $result = $finder->name($fileName)
            ->in($this->getSearchDirs())
            ->depth(0);
        
        if (count($result)) {
            return $result->getIterator()->current();
        }
        
        return null;
    }
    
    /**
     * Searches the composer.lock file
     *
     * @return SplFileInfo the found composer.lock or null if composer.lock isn't found
     */
    private function findComposerLock()
    {
        return $this->findComposerFile('composer.lock');
    }
    
    /**
     * Searches the composer.json file
     *
     * @return SplFileInfo the found composer.json or null if composer.json isn't found
     */
    private function findComposerJson()
    {
        return $this->findComposerFile('composer.json');
    }
    
    /**
     * Returns the directories to search the composer lock file in
     *
     * @return array[string]
     */
    private function getSearchDirs()
    {
        return [
            getcwd(),
            getcwd() . '/../',                   // cwd is a subfolder
            __DIR__ . '/../../../../../../../',  // vendor/propel/propel
            __DIR__ . '/../../../../'            // propel development environment
        ];
    }

    /**
     * Returns the loaded behaviors and loads them if not done before
     *
     * @return Map behaviors
     * @throws JsonException
     */
    public function getBehaviors(): Map
    {
        if (!isset($this->behaviors)) {
            // find behaviors in composer.lock file
            $lock = $this->findComposerLock();
            
            if (null !== $lock) {
                $this->behaviors = $this->loadBehaviors($lock);
            }
            
            // find behavior in composer.json (useful when developing a behavior)
            $json = $this->findComposerJson();
            
            if (null !== $json) {
                $behavior = $this->loadBehavior(Json::decode($json->getContents()));
                
                if (null !== $behavior) {
                    $this->behaviors[$behavior['name']] = $behavior;
                }
            }
        }
        
        return $this->behaviors;
    }
    
    /**
     * Returns the class name for a given behavior name
     *
     * @param  string                    $name The behavior name (e.g. timetampable)
     * @throws BehaviorNotFoundException|JsonException when the behavior cannot be found
     * @return string                    the class name
     */
    public function getClassname(string $name): string
    {
        if (false !== strpos($name, '\\')) {
            $class = $name;
        } else {
            $class = $this->getCoreBehavior($name);
            
            if (!class_exists($class)) {
                $behaviors = $this->getBehaviors();
                if ($behaviors->has($name)) {
                    $class = $behaviors[$name]['class'];
                }
            }
        }
        
        if (!class_exists($class)) {
            throw new BehaviorNotFoundException(sprintf('Unknown behavior "%s". You may try running `composer update` or passing the `--composer-dir` option.', $name));
        }
        
        return $class;
    }
    
    /**
     * Searches for the given behavior name in the Propel\Generator\Behavior namespace as
     * \Propel\Generator\Behavior\[Bname]\[Bname]Behavior
     *
     * @param  string $name The behavior name (ie: timestampable)
     * @return string The behavior fully qualified class name
     */
    private function getCoreBehavior(string $name): string
    {
        $phpName = Text::create($name)->toStudlyCase()->toString();
        
        return sprintf('\\Propel\\Generator\\Behavior\\%s\\%sBehavior', $phpName, $phpName);
    }

    /**
     * Finds all behaviors by parsing composer.lock file
     *
     * @param SplFileInfo $composerLock
     * @return Map
     * @throws JsonException
     */
    private function loadBehaviors(SplFileInfo $composerLock = null): Map
    {
        $behaviors =new Map();
        if (null !== $composerLock) {
            $json = Json::decode($composerLock->getContents());

            if (isset($json['packages'])) {
                foreach ($json['packages'] as $package) {
                    $behavior = $this->loadBehavior($package);

                    if (null !== $behavior) {
                        $behaviors->set($behavior['name'], $behavior);
                    }
                }
            }
        }
        
        return $behaviors;
    }
    
    /**
     * Reads the propel behavior data from a given composer package
     *
     * @param  array          $package
     * @throws BuildException
     * @return array          behavior data
     */
    private function loadBehavior(array $package): array
    {
        if (isset($package['type']) && $package['type'] == self::BEHAVIOR_PACKAGE_TYPE) {
            
            // find propel behavior information
            if (isset($package['extra'])) {
                $extra = $package['extra'];
                
                if (isset($extra['name']) && isset($extra['class'])) {
                    return [
                        'name' => $extra['name'],
                        'class' => $extra['class'],
                        'package' => $package['name']
                    ];
                } else {
                    throw new BuildException(sprintf('Cannot read behavior name and class from package %s', $package['name']));
                }
            }
        }
        
        return [];
    }
}
