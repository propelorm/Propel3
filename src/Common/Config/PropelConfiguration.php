<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config;

use Propel\Common\Types\SQL\ArrayType;
use Propel\Common\Types\SQL\BooleanType;
use Propel\Common\Types\SQL\DateTimeType;
use Propel\Common\Types\SQL\DoubleType;
use Propel\Common\Types\SQL\EnumType;
use Propel\Common\Types\SQL\IntegerType;
use Propel\Common\Types\SQL\LobType;
use Propel\Common\Types\SQL\ObjectType;
use Propel\Common\Types\SQL\VarcharType;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class PropelConfiguration
 *
 * This class performs validation of configuration array and assign default values
 *
 */
class PropelConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('propel');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->append($this->addGeneralSection())
            ->append($this->addPathsSection())
            ->append($this->addDatabaseSection())
            ->append($this->addMigrationsSection())
            ->append($this->addReverseSection())
            ->append($this->addRuntimeSection())
            ->append($this->addGeneratorSection())
            ->append($this->addFieldTypesSection())
        ;

        return $treeBuilder;
    }

    protected function addFieldTypesSection(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('types');

        return $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('varchar')->defaultValue(VarcharType::class)->end()
                ->scalarNode('char')->defaultValue(VarcharType::class)->end()
                ->scalarNode('longvarchar')->defaultValue(VarcharType::class)->end()
                ->scalarNode('boolean')->defaultValue(BooleanType::class)->end()

                ->scalarNode('integer')->defaultValue(IntegerType::class)->end()
                ->scalarNode('bigint')->defaultValue(IntegerType::class)->end()
                ->scalarNode('decimal')->defaultValue(IntegerType::class)->end()
                ->scalarNode('tinyint')->defaultValue(IntegerType::class)->end()

                ->scalarNode('double')->defaultValue(DoubleType::class)->end()
                ->scalarNode('float')->defaultValue(DoubleType::class)->end()

                ->scalarNode('datetime')->defaultValue(DateTimeType::class)->end()
                ->scalarNode('date')->defaultValue(DateTimeType::class)->end()
                ->scalarNode('time')->defaultValue(DateTimeType::class)->end()
                ->scalarNode('timestamp')->defaultValue(DateTimeType::class)->end()

                ->scalarNode('lob')->defaultValue(LobType::class)->end()
                ->scalarNode('clob')->defaultValue(LobType::class)->end()
                ->scalarNode('blob')->defaultValue(LobType::class)->end()

                ->scalarNode('object')->defaultValue(ObjectType::class)->end()
                ->scalarNode('array')->defaultValue(ArrayType::class)->end()
                ->scalarNode('enum')->defaultValue(EnumType::class)->end()
            ->end()
        ;
    }

    protected function addGeneralSection(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('general');

        return $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('project')->defaultValue('')->end()
                ->scalarNode('version')->defaultValue('3.0.0-dev')->end()
            ->end()
            ;
    }

    protected function addPathsSection(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('paths');

        return $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('projectDir')->defaultValue(getcwd())->end()
                ->scalarNode('schemaDir')->defaultValue('%projectDir%')->end()
                ->scalarNode('outputDir')->defaultValue('%projectDir%')->end()
                ->scalarNode('phpDir')->defaultValue('%projectDir%/generated-classes')->end()
                ->scalarNode('phpConfDir')->defaultValue('%projectDir%/generated-conf')->end()
                ->scalarNode('sqlDir')->defaultValue('%projectDir%/generated-sql')->end()
                ->scalarNode('migrationDir')->defaultValue('%projectDir%/generated-migrations')->end()
                ->scalarNode('composerDir')->defaultNull()->end()
            ->end()
        ;
    }

    protected function addDatabaseSection(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('database');

        return $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('connections')
                    ->isRequired()
                    ->validate()
                    ->always()
                        ->then(function ($connections) {
                            foreach ($connections as $name => $connection) {
                                if (strpos($name, '.') !== false) {
                                    throw new \InvalidArgumentException('Dots are not allowed in connection names');
                                }
                            }

                            return $connections;
                        })
                    ->end()
                    ->requiresAtLeastOneElement()
                    ->normalizeKeys(false)
                    ->prototype('array')
                    ->fixXmlConfig('slave')
                        ->children()
                            ->scalarNode('classname')->defaultValue('\Propel\Runtime\Connection\ConnectionWrapper')->end()
                            ->enumNode('adapter')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->values(['mysql', 'pgsql', 'sqlite', 'mssql', 'sqlsrv', 'oracle'])
                            ->end()
                            ->scalarNode('dsn')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('user')->isRequired()->end()
                            ->scalarNode('password')->isRequired()->treatNullLike('')->end()
                            ->arrayNode('options')
                                ->children()
                                    ->booleanNode('ATTR_PERSISTENT')->defaultFalse()->end()
                                    ->scalarNode('MYSQL_ATTR_SSL_CA')->end()
                                    ->scalarNode('MYSQL_ATTR_SSL_CERT')->end()
                                    ->scalarNode('MYSQL_ATTR_SSL_KEY')->end()
                                    ->scalarNode('MYSQL_ATTR_MAX_BUFFER_SIZE')->end()
                                ->end()
                            ->end()
                            ->arrayNode('attributes')
                                ->children()
                                    ->booleanNode('ATTR_EMULATE_PREPARES')->defaultFalse()->end()
                                ->end()
                            ->end()
                            ->arrayNode('settings')
                            ->fixXmlConfig('query', 'queries')
                                ->children()
                                    ->scalarNode('charset')->defaultValue('utf8')->end()
                                    ->arrayNode('queries')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('slaves')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('dsn')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('adapters')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mysql')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('tableType')->defaultValue('InnoDB')->treatNullLike('InnoDB')->end()
                                ->scalarNode('tableEngineKeyword')->defaultValue('ENGINE')->end()
                            ->end()
                        ->end()
                        ->arrayNode('sqlite')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('foreignKey')->end()
                                ->scalarNode('tableAlteringWorkaround')->end()
                            ->end()
                        ->end()
                        ->arrayNode('oracle')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('autoincrementSequencePattern')->defaultValue('${table}_SEQ')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() //adapters
            ->end()
        ;
    }

    protected function addMigrationsSection(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('migrations');

        return $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('samePhpName')->defaultFalse()->end()
                ->booleanNode('addVendorInfo')->defaultFalse()->end()
                ->scalarNode('tableName')->defaultValue('propel_migration')->end()
                ->scalarNode('parserClass')->defaultNull()->end()
            ->end()
        ;
    }

    protected function addReverseSection(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('reverse');

        return $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('connection')->end()
                ->scalarNode('parserClass')->end()
            ->end()
        ;
    }

    protected function addRuntimeSection(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('runtime');

        return $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->fixXmlConfig('connection')
            ->children()
                ->scalarNode('defaultConnection')->defaultValue('default')->end()
                ->arrayNode('connections')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('log')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->end()
                            ->scalarNode('path')->end()
                            ->enumNode('level')->values([100, 200, 250, 300, 400, 500, 550, 600])->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('profiler')
                    ->children()
                        ->scalarNode('classname')->defaultValue('\Propel\Runtime\Util\Profiler')->end()
                        ->floatNode('slowTreshold')->defaultValue(0.1)->end()
                        ->arrayNode('details')
                            ->children()
                                ->arrayNode('time')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                        ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('memory')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                        ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('memDelta')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                        ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('memPeak')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                        ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('innerGlue')->defaultValue(':')->end()
                        ->scalarNode('outerGlue')->defaultValue('|')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addGeneratorSection(): NodeDefinition
    {
        $treeBuilder= new TreeBuilder('generator');

        return $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->fixXmlConfig('connection')
            ->children()
                ->scalarNode('defaultConnection')->isRequired()->end()
                ->arrayNode('connections')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('schema')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('basename')->defaultValue('schema')->end()
                        ->booleanNode('autoNamespace')->defaultFalse()->end()
                        ->booleanNode('transform')->defaultFalse()->end()
                    ->end()
                ->end() //schema
                ->arrayNode('dateTime')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('useDateTimeClass')->defaultTrue()->end()
                        ->scalarNode('dateTimeClass')->defaultValue('DateTime')->end()
                        ->scalarNode('defaultTimeStampFormat')->end()
                        ->scalarNode('defaultTimeFormat')->end()
                        ->scalarNode('defaultDateFormat')->end()
                    ->end()
                ->end()
                ->arrayNode('objectModel')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('addGenericAccessors')->defaultTrue()->end()
                        ->booleanNode('addGenericMutators')->defaultTrue()->end()
                        ->booleanNode('emulateForeignKeyConstraints')->defaultFalse()->end()
                        ->booleanNode('addClassLevelComment')->defaultTrue()->end()
                        ->scalarNode('defaultKeyType')->defaultValue('fieldName')->end()
                        ->booleanNode('addSaveMethod')->defaultTrue()->end()
                        ->scalarNode('namespaceMap')->defaultValue('Map')->end()
                        ->booleanNode('addTimeStamp')->defaultFalse()->end()
                        ->booleanNode('addHooks')->defaultTrue()->end()
                        ->scalarNode('classPrefix')->defaultNull()->end()
                        ->booleanNode('useLeftJoinsInDoJoinMethods')->defaultTrue()->end()
                        ->arrayNode('builders')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('object')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\ObjectBuilder')->end()
                                ->scalarNode('activerecordtrait')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\ActiveRecordTraitBuilder')->end()
                                ->scalarNode('objectmultiextend')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\MultiExtendObjectBuilder')->end()
                                ->scalarNode('repository')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\RepositoryBuilder')->end()
                                ->scalarNode('repositorystub')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\StubRepositoryBuilder')->end()
                                ->scalarNode('query')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\QueryBuilder')->end()
                                ->scalarNode('proxy')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\ProxyBuilder')->end()
                                ->scalarNode('querystub')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\StubQueryBuilder')->end()
                                ->scalarNode('queryinheritance')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\QueryInheritanceBuilder')->end()
                                ->scalarNode('queryinheritancestub')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\StubQueryInheritanceBuilder')->end()
                                ->scalarNode('inheritanceentitymap')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\EntityMapInheritanceBuilder')->end()
                                ->scalarNode('entitymap')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\EntityMapBuilder')->end()
                                ->scalarNode('datasql')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() //objectModel
            ->end()
        ;
    }
}
