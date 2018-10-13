<?php
namespace Propel\Generator\Manager;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Exception\BehaviorNotFoundException;
use Propel\Generator\Model\NamingTool;

/**
 * Behavior manager to locate and instantiate behaviors
 *
 * @author Thomas Gossmann
 *
 */
class BehaviorManager
{
    const BEHAVIOR_PACKAGE_TYPE = 'propel-behavior';
    
    private $behaviors = null;
    
    private $composerDir = null;
    
    /**
     * Creates a new behavior manager
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig = null;
    
    public function __construct(GeneratorConfigInterface $config = null)
    {
        $this->setGeneratorConfig($config);
    }
    
    /**
     * Sets the generator config
     *
     * @param GeneratorConfigInterface $config build config
     */
    public function setGeneratorConfig(GeneratorConfigInterface $config = null)
    {
        $this->generatorConfig = $config;
        $this->composerDir = null;
        $this->behaviors = null;
        
        if (null !== $config) {
            $this->composerDir = $config->get()['paths']['composerDir'];
        }
    }
    
    /**
     * Instantiates a behavior from a given name
     *
     * @param string $name
     * @throws BuildException
     * @return Behavior
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
     * @return SplFileInfo the found composer file or null if composer file isn't found
     */
    private function findComposerFile($fileName)
    {
        if (null !== $this->composerDir) {
            $filePath = $this->composerDir . '/' . $fileName;
            
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
     * @return array behaviors
     */
    public function getBehaviors()
    {
        if (null === $this->behaviors) {
            // find behaviors in composer.lock file
            $lock = $this->findComposerLock();
            
            if (null === $lock) {
                $this->behaviors = [];
            } else {
                $this->behaviors = $this->loadBehaviors($lock);
            }
            
            // find behavior in composer.json (useful when developing a behavior)
            $json = $this->findComposerJson();
            
            if (null !== $json) {
                $behavior = $this->loadBehavior(json_decode($json->getContents(), true));
                
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
     * @throws BehaviorNotFoundException when the behavior cannot be found
     * @return string                    the class name
     */
    public function getClassname($name)
    {
        if (false !== strpos($name, '\\')) {
            $class = $name;
        } else {
            $class = $this->getCoreBehavior($name);
            
            if (!class_exists($class)) {
                $behaviors = $this->getBehaviors();
                if (array_key_exists($name, $behaviors)) {
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
    private function getCoreBehavior($name)
    {
        $phpName = NamingTool::toStudlyCase($name);
        
        return sprintf('\\Propel\\Generator\\Behavior\\%s\\%sBehavior', $phpName, $phpName);
    }
    
    /**
     * Finds all behaviors by parsing composer.lock file
     *
     * @param SplFileInfo $composerLock
     */
    private function loadBehaviors($composerLock)
    {
        $behaviors = [];
        
        if (null === $composerLock) {
            return $behaviors;
        }
        
        $json = json_decode($composerLock->getContents(), true);
        
        if (isset($json['packages'])) {
            foreach ($json['packages'] as $package) {
                $behavior = $this->loadBehavior($package);
                
                if (null !== $behavior) {
                    $behaviors[$behavior['name']] = $behavior;
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
    private function loadBehavior($package)
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
        
        return null;
    }
}
