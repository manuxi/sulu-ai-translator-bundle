<?php

declare(strict_types=1);

namespace Robole\SuluAITranslatorBundle\Tests;

use PHPUnit\Framework\TestCase;
use Robole\SuluAITranslatorBundle\DependencyInjection\SuluAITranslatorExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ExtensionTest extends TestCase
{
    public function testParametersFollowTheConfiguration(): void
    {
        $container = $this->load(['usage_statistics' => ['settings_page' => false, 'dashboard' => false]]);

        self::assertFalse($container->getParameter('sulu_ai_translator.usage_statistics.settings_page'));
        self::assertFalse($container->getParameter('sulu_ai_translator.usage_statistics.dashboard'));
    }

    public function testDefaultsKeepEverythingAsBefore(): void
    {
        $container = $this->load([]);

        self::assertTrue($container->getParameter('sulu_ai_translator.usage_statistics.settings_page'));
        self::assertTrue($container->getParameter('sulu_ai_translator.usage_statistics.dashboard'));
        self::assertTrue($container->hasDefinition('ai_translator.admin'));
    }

    public function testTileIsNotLoadedWhenSwitchedOff(): void
    {
        self::assertFalse($this->load(['usage_statistics' => ['dashboard' => false]])->hasDefinition('ai_translator.dashboard_widget'));
    }

    public function testTileIsOnlyLoadedWhenTheDashboardInterfaceIsAvailable(): void
    {
        $available = interface_exists('Manuxi\SuluDashboardBundle\Widget\DashboardWidgetInterface');

        self::assertSame($available, $this->load([])->hasDefinition('ai_translator.dashboard_widget'));
    }

    public function testTileServiceDefinition(): void
    {
        $container = $this->load([]);
        (new XmlFileLoader($container, new FileLocator(dirname(__DIR__) . '/src/Resources/config')))->load('dashboard.xml');

        $definition = $container->getDefinition('ai_translator.dashboard_widget');

        self::assertTrue($definition->isAutoconfigured());
        self::assertSame('ai_translator.deepl_service', (string) $definition->getArgument(0)->getValues()[0]);
        self::assertSame('%sulu_ai_translator.deepl_api_key%', $definition->getArgument(1));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new SuluAITranslatorExtension())->load([array_merge(['deepl_api_key' => 'key'], $config)], $container);

        return $container;
    }
}
