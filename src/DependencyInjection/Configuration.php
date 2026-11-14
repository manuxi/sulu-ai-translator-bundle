<?php

declare(strict_types=1);

namespace Robole\SuluAITranslatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_ai_translator');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('deepl_api_key')
                    ->end()
                ->arrayNode('locale_mapping')
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()
                    ->end()
                ->end()
                ->arrayNode('toolbar')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_attach')
                            ->defaultTrue()
                            ->info('Add the bulk translate button to every localized form view with a save action.')
                        ->end()
                        ->arrayNode('views')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                            ->info('Names of additional views that always get the bulk translate button.')
                        ->end()
                        ->arrayNode('exclude')
                            ->scalarPrototype()->end()
                            ->defaultValue(['*.settings', '*.add_tabs*', '*.add_form*'])
                            ->info('Patterns (fnmatch) of view names that never get the bulk translate button automatically.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('usage_statistics')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('settings_page')
                            ->defaultTrue()
                            ->info('Show the usage statistics page in the settings navigation.')
                        ->end()
                        ->booleanNode('dashboard')
                            ->defaultTrue()
                            ->info('Register the usage tile at manuxi/sulu-dashboard-bundle (only if that bundle is installed).')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('permissions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                            ->info('Restrict translating to roles with the permission of the area (user roles > AI Translator).')
                        ->end()
                        ->arrayNode('areas')
                            ->scalarPrototype()->end()
                            ->defaultValue(['pages', 'articles', 'snippets'])
                            ->info('Resource keys that get their own permission. All other resources share the area "other".')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
