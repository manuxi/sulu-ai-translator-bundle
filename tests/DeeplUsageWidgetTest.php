<?php

declare(strict_types=1);

namespace Robole\SuluAITranslatorBundle\Tests;

use DeepL\AuthorizationException;
use DeepL\ConnectionException;
use DeepL\DeepLException;
use Manuxi\SuluDashboardBundle\Widget\DashboardWidgetInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Robole\SuluAITranslatorBundle\Admin\AITranslatorAdmin;
use Robole\SuluAITranslatorBundle\Dashboard\DeeplUsageWidget;
use Robole\SuluAITranslatorBundle\Service\DeeplService;

class DeeplUsageWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(DashboardWidgetInterface::class)) {
            self::markTestSkipped('manuxi/sulu-dashboard-bundle is not available (set SULU_DASHBOARD_BUNDLE_PATH).');
        }
    }

    public function testMetaData(): void
    {
        $widget = $this->widget(null);

        self::assertInstanceOf(DashboardWidgetInterface::class, $widget);
        self::assertSame('deepl_usage', $widget->getKey());
        self::assertSame('progress', $widget->getType());
        self::assertSame('side', $widget->getColumn());
        self::assertSame(AITranslatorAdmin::SECURITY_CONTEXT, $widget->getSecurityContext());
        self::assertSame('app.translator_config_headline', $widget->getTitle());
    }

    public function testShowsUsageAsBar(): void
    {
        $data = $this->widget($this->service((object) ['character_count' => 123456, 'character_limit' => 500000]))->getData('de');

        self::assertSame('app.translator_config_usage_statistics', $data['text']['key']);
        self::assertSame(['characterCount' => '123.456', 'characterLimit' => '500.000'], $data['text']['parameters']);
        self::assertSame('bar', $data['display']);
        self::assertCount(1, $data['items']);
        self::assertSame(123456, $data['items'][0]['value']);
        self::assertSame(500000, $data['items'][0]['max']);
        self::assertSame('25 %', $data['items'][0]['text']);
        self::assertSame('progress', $data['items'][0]['skin']);
    }

    #[DataProvider('skins')]
    public function testSkinGrowsWithUsage(int $count, string $skin): void
    {
        $data = $this->widget($this->service((object) ['character_count' => $count, 'character_limit' => 1000]))->getData('en');

        self::assertSame($skin, $data['items'][0]['skin']);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function skins(): iterable
    {
        yield 'low' => [100, 'progress'];
        yield 'almost full' => [800, 'warning'];
        yield 'full' => [1000, 'error'];
    }

    public function testUsageAboveLimitStaysInsideTheBar(): void
    {
        $data = $this->widget($this->service((object) ['character_count' => 1200, 'character_limit' => 1000]))->getData('en');

        self::assertSame(1000, $data['items'][0]['value']);
        self::assertSame('error', $data['items'][0]['skin']);
    }

    public function testMissingApiKeyIsExplainedAndDeeplIsNotCalled(): void
    {
        $widget = new DeeplUsageWidget(static function (): DeeplService {
            throw new \LogicException('DeepL must not be created without an API key.');
        }, '  ');

        $data = $widget->getData('de');

        self::assertSame('app.translator_dashboard_no_key', $data['text']);
        self::assertSame([], $data['items']);
    }

    #[DataProvider('problems')]
    public function testProblemsAreExplainedInsteadOfThrown(\Throwable $exception, string $expected): void
    {
        $service = $this->createMock(DeeplService::class);
        $service->method('getUsage')->willThrowException($exception);

        $data = $this->widget($service)->getData('de');

        self::assertSame($expected, $data['text']);
        self::assertSame([], $data['items']);
    }

    /**
     * @return iterable<string, array{\Throwable, string}>
     */
    public static function problems(): iterable
    {
        yield 'key rejected' => [new AuthorizationException('Authorization failure'), 'app.translator_dashboard_key_rejected'];
        yield 'not reachable' => [new ConnectionException('Connection failure', 0, null, false), 'app.translator_dashboard_unreachable'];
        yield 'other DeepL error' => [new DeepLException('Something else'), 'app.translator_dashboard_failed'];
    }

    public function testAccountWithoutCharacterUsage(): void
    {
        $data = $this->widget($this->service(null))->getData('de');

        self::assertSame('app.translator_dashboard_no_usage', $data['text']);
    }

    public function testEveryTranslationKeyExistsInAllLanguages(): void
    {
        $keys = [
            'app.translator_config_headline',
            'app.translator_config_usage_statistics',
            'app.translator_dashboard_characters',
            'app.translator_dashboard_manage',
            'app.translator_dashboard_no_key',
            'app.translator_dashboard_key_rejected',
            'app.translator_dashboard_unreachable',
            'app.translator_dashboard_failed',
            'app.translator_dashboard_no_usage',
        ];

        foreach (glob(dirname(__DIR__) . '/src/Resources/translations/admin.*.json') ?: [] as $file) {
            $translations = json_decode((string) file_get_contents($file), true, 512, \JSON_THROW_ON_ERROR);

            foreach ($keys as $key) {
                self::assertArrayHasKey($key, $translations, basename($file));
            }
        }
    }

    private function service(?object $usage): DeeplService
    {
        $service = $this->createMock(DeeplService::class);
        $service->method('getUsage')->willReturn($usage);

        return $service;
    }

    private function widget(?DeeplService $service): DeeplUsageWidget
    {
        return new DeeplUsageWidget(static fn (): DeeplService => $service ?? throw new \LogicException('not needed'), 'test-key:fx');
    }
}
