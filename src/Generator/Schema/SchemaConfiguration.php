<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Schema;

use phootwork\lang\Text;
use Propel\Generator\Model\Model;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class SchemaConfiguration
 *
 * This class performs validation of schema array and assign default values
 *
 */
class SchemaConfiguration implements ConfigurationInterface
{
    /**
     * Generates the schema tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('database');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
                ->fixXmlConfig('entity', 'entities')
                ->fixXmlConfig('behavior')
                ->fixXmlConfig('external_schema', 'external-schemas')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->enumNode('defaultIdMethod')
                        ->values([Model::ID_METHOD_NONE, Model::ID_METHOD_NATIVE])
                        ->defaultValue(Model::ID_METHOD_NATIVE)
                    ->end()
                    ->scalarNode('namespace')->end()
                    ->booleanNode('activeRecord')->defaultFalse()->end()
                    ->booleanNode('identifierQuoting')->end()
                    ->scalarNode('defaultStringFormat')->end()
                    ->booleanNode('heavyIndexing')->defaultFalse()->end()
                    ->scalarNode('baseClass')->end()
                    ->scalarNode('schema')->end()
                    ->append($this->getExternalSchemasNode())
                    ->append($this->getBehaviorNode())
                    ->append($this->getVendorNode())
                    ->arrayNode('entities')
                        ->requiresAtLeastOneElement()
                        ->arrayPrototype()
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('field')
                            ->fixXmlConfig('behavior')
                            ->fixXmlConfig('relation')
                            ->fixXmlConfig('index', 'indices')
                            ->fixXmlConfig('unique', 'uniques')
                            ->children()
                                ->scalarNode('name')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->beforeNormalization()
                                        ->always(fn(string $name): string => Text::create($name)->toStudlyCase()->toString())
                                    ->end()
                                ->end()
                                ->scalarNode('tableName')->end()
                                ->enumNode('idMethod')
                                    ->values(['native', 'autoincrement', 'sequence', 'none', null])
                                    ->defaultNull()
                                ->end()
                                ->booleanNode('skipSql')->defaultFalse()->end()
                                ->booleanNode('readOnly')->defaultFalse()->end()
                                ->booleanNode('abstract')->defaultFalse()->end()
                                ->booleanNode('isCrossRef')->defaultFalse()->end()
                                ->scalarNode('schema')->end()
                                ->scalarNode('namespace')->end()
                                ->booleanNode('identifierQuoting')->end()
                                ->scalarNode('description')->end()
                                ->booleanNode('activeRecord')->end()
                                ->booleanNode('reloadOnInsert')->defaultFalse()->end()
                                ->booleanNode('reloadOnUpdate')->defaultFalse()->end()
                                ->booleanNode('allowPkInsert')->defaultFalse()->end()
                                ->booleanNode('heavyIndexing')->end()
                                ->scalarNode('defaultStringFormat')->end()
                                ->append($this->getFieldsNode())
                                ->append($this->getRelationsNode())
                                ->append($this->getIndicesNode())
                                ->append($this->getUniquesNode())
                                ->append($this->getBehaviorNode())
                                ->append($this->getVendorNode())
                                ->arrayNode('id_method_parameter')
                                    ->children()
                                        ->scalarNode('value')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function getParametersNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('parameters');

        return $treeBuilder->getRootNode()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('name')->end()
                    ->scalarNode('value')->end()
                ->end()
            ->end()
        ;
    }

    private function getBehaviorNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('behaviors');

        return $treeBuilder->getRootNode()
            ->beforeNormalization()
            ->always(function(array $behaviors): array {
                foreach ($behaviors as $key => $behavior) {
                    if (!isset($behavior['id'])) {
                        $behaviors[$key]['id'] = $behavior['name'];
                    }
                }

                return $behaviors;
            })
            ->end()
            ->useAttributeAsKey('id')
            ->arrayPrototype()
                ->fixXmlConfig('parameter')
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->append($this->getParametersNode())
                ->end()
            ->end()
        ;
    }

    private function getVendorNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('vendor');

        return $treeBuilder->getRootNode()
            ->fixXmlConfig('parameter')
            ->children()
                ->enumNode('type')->values(['mysql', 'MYSQL','oracle', 'ORACLE', 'pgsql', 'PGSQL'])->end()
                ->append($this->getParametersNode())
            ->end()
        ;
    }

    private function getExternalSchemasNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('external-schemas');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('filename')->end()
                    ->booleanNode('referenceOnly')->defaultTrue()->end()
                ->end()
            ->end()
        ;
    }

    private function getInheritancesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('inheritances');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->children()
                    ->scalarNode('key')->isRequired()->end()
                    ->scalarNode('class')->isRequired()->end()
                    ->scalarNode('extends')->end()
                ->end()
            ->end()
        ;
    }

    private function getFieldsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('fields');

        return $treeBuilder->getRootNode()
            ->requiresAtLeastOneElement()
            ->fixXmlConfig('inheritance')
            ->arrayPrototype()
            ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->booleanNode('primaryKey')->defaultFalse()->end()
                    ->booleanNode('required')->defaultFalse()->end()
                    ->enumNode('type')
                        ->beforeNormalization()->always(fn(string $variable): string => strtoupper($variable))->end()
                        ->values(['BIT', 'TINYINT', 'SMALLINT', 'INTEGER', 'BIGINT', 'FLOAT',
                            'REAL', 'NUMERIC', 'DECIMAL', 'CHAR', 'VARCHAR', 'LONGVARCHAR',
                            'DATE', 'TIME', 'TIMESTAMP', 'BINARY', 'VARBINARY', 'LONGVARBINARY',
                            'NULL', 'OTHER', 'PHP_OBJECT', 'DISTINCT', 'STRUCT', 'ARRAY',
                            'BLOB', 'CLOB', 'REF', 'BOOLEANINT', 'BOOLEANCHAR', 'DOUBLE',
                            'BOOLEAN', 'OBJECT', 'ENUM'
                        ])
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->defaultValue('VARCHAR')
                    ->end()
                    ->scalarNode('phpType')->end()
                    ->scalarNode('sqlType')->end()
                    ->integerNode('size')->end()
                    ->integerNode('scale')->end()
                    ->scalarNode('default')->end()
                    ->scalarNode('defaultValue')->end()
                    ->scalarNode('defaultExpr')->end()
                    ->booleanNode('autoIncrement')->defaultFalse()->end()
                    ->enumNode('inheritance')->values(['single', 'none'])->defaultValue('none')->end()
                    ->scalarNode('description')->end()
                    ->booleanNode('lazyLoad')->defaultFalse()->end()
                    ->booleanNode('primaryString')->defaultFalse()->end()
                    ->scalarNode('valueSet')->end()
                    ->append($this->getInheritancesNode())
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;
    }

    private function getRelationsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('relations');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->fixXmlConfig('reference')
                ->children()
                    ->scalarNode('target')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->beforeNormalization()
                            ->always(fn(string $name): string => Text::create($name)->toStudlyCase()->toString())
                        ->end()
                    ->end()
                    ->scalarNode('field')->end()
                    ->scalarNode('name')->end()
                    ->scalarNode('refField')->end()
                    ->scalarNode('refName')->end()
                    ->scalarNode('foreignSchema')->end()
                    ->enumNode('onUpdate')
                        ->beforeNormalization()
                            ->always(fn(string $variable): string => strtoupper($variable))
                        ->end()
                        ->values(['CASCADE', 'SETNULL', 'RESTRICT', 'NONE'])
                    ->end()
                    ->enumNode('onDelete')
                        ->beforeNormalization()
                            ->always(fn(string $variable): string => strtoupper($variable))
                        ->end()
                        ->values(['CASCADE', 'SETNULL', 'RESTRICT', 'NONE'])
                    ->end()
                    ->enumNode('defaultJoin')
                        ->beforeNormalization()
                            ->always(fn(string $variable): string => strtoupper($variable))
                        ->end()
                        ->values(['INNER JOIN', 'LEFT JOIN'])
                    ->end()
                    ->booleanNode('skipSql')->defaultFalse()->end()
                    ->arrayNode('references')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('local')->isRequired()->cannotBeEmpty()->end()
                                ->scalarNode('foreign')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;
    }

    private function getIndicesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('indices');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->fixXmlConfig('index-field', 'index-fields')
                ->normalizeKeys(false)
                ->children()
                    ->scalarNode('name')->end()
                    ->arrayNode('index-fields')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->integerNode('size')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;
    }

    private function getUniquesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('uniques');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->fixXmlConfig('unique-field', 'unique-fields')
                ->normalizeKeys(false)
                ->children()
                    ->scalarNode('name')->end()
                    ->arrayNode('unique-fields')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->integerNode('size')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;
    }
}
